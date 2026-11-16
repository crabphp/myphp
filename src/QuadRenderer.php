<?php

declare(strict_types=1);

namespace PhpUi;

use GL\Buffer\FloatBuffer;

/**
 * Draws boxes the way GPUI and MyGo do: one instanced quad per box, and a
 * fragment shader that computes rounded corners (one radius per corner),
 * borders, vertical gradients and blurred shadows from a signed distance.
 * The whole batch is one draw call, with no stencil passes.
 */
final class QuadRenderer
{
    /** Floats per instance: rect, radii, color top, color bottom, border color, clip, params. */
    private const STRIDE = 28;

    private int $program;
    private int $vao = 0;
    private int $vbo = 0;
    private int $uView;
    private int $uScale;
    /** @var float[] */
    private array $data = [];
    private int $count = 0;

    public function __construct()
    {
        $this->program = $this->link(self::VERTEX, self::FRAGMENT);
        $this->uView = glGetUniformLocation($this->program, 'uView');
        $this->uScale = glGetUniformLocation($this->program, 'uScale');

        glGenVertexArrays(1, $this->vao);
        glGenBuffers(1, $this->vbo);
        glBindVertexArray($this->vao);
        glBindBuffer(GL_ARRAY_BUFFER, $this->vbo);
        for ($i = 0; $i < 7; $i++) {
            glEnableVertexAttribArray($i);
            glVertexAttribPointer($i, 4, GL_FLOAT, false, self::STRIDE * 4, $i * 16);
            glVertexAttribDivisor($i, 1);
        }
        glBindVertexArray(0);
    }

    public function begin(): void
    {
        $this->data = [];
        $this->count = 0;
    }

    /**
     * One box. Colors are [r, g, b, a] in 0..1, not premultiplied. With $blur > 0
     * the box is a shadow: $top is its color, feathered over $blur px. A shadow is
     * not drawn under its element, found from the shadow's $spread and $offsetY,
     * so boxes with a see-through background stay clear, as with CSS box-shadow.
     */
    public function add(array $rect, array $radii, array $top, array $bottom, array $border, float $borderW, float $blur, array $clip, float $spread = 0, float $offsetY = 0): void
    {
        array_push($this->data, ...$rect, ...$radii, ...$top, ...$bottom, ...$border, ...$clip, ...[$borderW, $blur, $spread, $offsetY]);
        $this->count++;
    }

    public function flush(float $w, float $h, float $ratio): void
    {
        if ($this->count === 0) return;
        glUseProgram($this->program);
        glUniform2f($this->uView, $w, $h);
        glUniform1f($this->uScale, $ratio);
        glBindVertexArray($this->vao);
        glBindBuffer(GL_ARRAY_BUFFER, $this->vbo);
        // FloatBuffer only takes floats: convert the batch once, not per value.
        glBufferData(GL_ARRAY_BUFFER, new FloatBuffer(array_map('floatval', $this->data)), GL_STREAM_DRAW);
        glEnable(GL_BLEND);
        glBlendFunc(GL_ONE, GL_ONE_MINUS_SRC_ALPHA); // premultiplied, as NanoVG
        glDisable(GL_DEPTH_TEST);
        glDisable(GL_CULL_FACE);
        glDisable(GL_STENCIL_TEST);
        glDisable(GL_SCISSOR_TEST);
        glDrawArraysInstanced(GL_TRIANGLE_STRIP, 0, 4, $this->count);
        glBindVertexArray(0);
        glUseProgram(0);
    }

    private function link(string $vertex, string $fragment): int
    {
        $program = glCreateProgram();
        foreach ([GL_VERTEX_SHADER => $vertex, GL_FRAGMENT_SHADER => $fragment] as $type => $source) {
            $shader = glCreateShader($type);
            glShaderSource($shader, $source);
            glCompileShader($shader);
            glGetShaderiv($shader, GL_COMPILE_STATUS, $ok);
            if (!$ok) throw new \RuntimeException('Quad shader: ' . glGetShaderInfoLog($shader, 4096));
            glAttachShader($program, $shader);
            glDeleteShader($shader);
        }
        glLinkProgram($program);
        glGetProgramiv($program, GL_LINK_STATUS, $ok);
        if (!$ok) throw new \RuntimeException('Quad program: ' . glGetProgramInfoLog($program, 4096));
        return $program;
    }

    private const VERTEX = <<<'GLSL'
        #version 330 core
        layout(location = 0) in vec4 aRect;
        layout(location = 1) in vec4 aRadii;
        layout(location = 2) in vec4 aTop;
        layout(location = 3) in vec4 aBottom;
        layout(location = 4) in vec4 aBorder;
        layout(location = 5) in vec4 aClip;
        layout(location = 6) in vec4 aParams;
        uniform vec2 uView;
        out vec2 vPos;
        flat out vec4 vRect, vRadii, vTop, vBottom, vBorder, vParams;

        void main() {
            vec2 corner = vec2(gl_VertexID & 1, gl_VertexID >> 1);
            float pad = aParams.y + 1.0;
            // Clip by shrinking the quad: clips are rectangles, so no scissor or stencil is needed.
            vec2 lo = max(aRect.xy - pad, aClip.xy);
            vec2 hi = max(lo, min(aRect.xy + aRect.zw + pad, aClip.xy + aClip.zw));
            vPos = mix(lo, hi, corner);
            vRect = aRect; vRadii = aRadii; vTop = aTop; vBottom = aBottom; vBorder = aBorder; vParams = aParams;
            gl_Position = vec4(vPos.x / uView.x * 2.0 - 1.0, 1.0 - vPos.y / uView.y * 2.0, 0.0, 1.0);
        }
        GLSL;

    private const FRAGMENT = <<<'GLSL'
        #version 330 core
        in vec2 vPos;
        flat in vec4 vRect, vRadii, vTop, vBottom, vBorder, vParams;
        uniform float uScale;
        out vec4 fragColor;

        // Signed distance to a rounded box; radii are top-left, top-right, bottom-right, bottom-left.
        float roundedBox(vec2 p, vec2 center, vec2 halfSize, vec4 r) {
            vec2 q = p - center;
            float radius = q.x < 0.0 ? (q.y < 0.0 ? r.x : r.w) : (q.y < 0.0 ? r.y : r.z);
            radius = min(radius, min(halfSize.x, halfSize.y));
            vec2 d = abs(q) - halfSize + radius;
            return length(max(d, 0.0)) + min(max(d.x, d.y), 0.0) - radius;
        }

        vec4 premul(vec4 c) { return vec4(c.rgb * c.a, c.a); }

        void main() {
            vec2 halfSize = vRect.zw * 0.5;
            float d = roundedBox(vPos, vRect.xy + halfSize, halfSize, vRadii);
            float blur = vParams.y;
            if (blur > 0.0) {
                // The element itself: the shadow rect moved back by the offset and spread.
                float spread = vParams.z;
                vec2 holeHalf = max(halfSize - spread, vec2(0.0));
                vec2 holeCenter = vRect.xy + halfSize - vec2(0.0, vParams.w);
                float hole = roundedBox(vPos, holeCenter, holeHalf, max(vRadii - spread, vec4(0.0)));
                float outside = clamp(0.5 + hole * uScale, 0.0, 1.0);
                fragColor = premul(vTop) * (1.0 - smoothstep(-0.5 * blur, 0.5 * blur, d)) * outside;
                return;
            }
            // Coverage over one device pixel gives the anti-aliased edge.
            float outer = clamp(0.5 - d * uScale, 0.0, 1.0);
            float t = clamp((vPos.y - vRect.y) / max(vRect.w, 1.0), 0.0, 1.0);
            vec4 fill = premul(mix(vTop, vBottom, t)) * outer;
            float bw = vParams.x;
            if (bw > 0.0) {
                float inner = clamp(0.5 - (d + bw) * uScale, 0.0, 1.0);
                vec4 band = premul(vBorder) * (outer - inner);
                fill = band + fill * (1.0 - band.a);
            }
            fragColor = fill;
        }
        GLSL;
}
