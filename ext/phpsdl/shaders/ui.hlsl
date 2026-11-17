// phpsdl's UI shader for SDL_GPU: boxes (rounded corners, borders, gradients, shadows
// from signed distances), coverage masks (glyphs, icons) and images, one instanced
// quad each. Compiled by build.sh to SPIR-V, DXIL and MSL. Same math as render.c's GLSL.

struct Instance
{
    float4 rect   : TEXCOORD0;
    float4 radii  : TEXCOORD1;
    float4 top    : TEXCOORD2;
    float4 bottom : TEXCOORD3; // uv rectangle for masks and images
    float4 border : TEXCOORD4; // the box an image fills
    float4 clip   : TEXCOORD5;
    float4 params : TEXCOORD6; // border width, blur, spread, shadow offset
    float4 kind   : TEXCOORD7; // x: 0 box, 1 mask (y: 1 when color), 2 image (y: 1 when transformed), 3 ground,
                               // 4 terrain (y: its texture's width); z: depth (distance from the camera, 0 in front)
};

struct Varyings
{
    float4 position : SV_Position;
    float2 pos : TEXCOORD0;
    float2 uv : TEXCOORD1;
    nointerpolation float4 rect : TEXCOORD2;
    nointerpolation float4 radii : TEXCOORD3;
    nointerpolation float4 top : TEXCOORD4;
    nointerpolation float4 bottom : TEXCOORD5;
    nointerpolation float4 border : TEXCOORD6;
    nointerpolation float4 params : TEXCOORD7;
    nointerpolation float4 kind : TEXCOORD8;
    nointerpolation float4 clip : TEXCOORD9;
};

[[vk::binding(0, 1)]]
cbuffer VertexUniforms : register(b0, space1)
{
    float2 view;
    float2 vertexPad;
};

// A distance from the camera as a depth buffer value: 0 (in front of everything) to 1.
float depthOf(float d) { return d > 0.0 ? d / (d + 256.0) : 0.0; }

Varyings VSMain(Instance i, uint id : SV_VertexID)
{
    float2 corner = float2(id & 1, id >> 1);
    float pad = i.kind.x == 0.0 ? i.params.y + 1.0 : 0.0;
    // Clip by shrinking the quad, and keep texture coordinates on the unclipped rectangle.
    float2 lo = max(i.rect.xy - pad, i.clip.xy);
    float2 hi = max(lo, min(i.rect.xy + i.rect.zw + pad, i.clip.xy + i.clip.zw));
    Varyings o;
    if (i.kind.x == 2.0 && i.kind.y == 1.0) {
        // A transformed image: rect in its own units, radii the 2×2 matrix, border.xy the offset.
        float2 local = lerp(i.rect.xy, i.rect.xy + i.rect.zw, corner);
        o.pos = float2(i.radii.x * local.x + i.radii.z * local.y, i.radii.y * local.x + i.radii.w * local.y) + i.border.xy;
        o.uv = lerp(i.bottom.xy, i.bottom.zw, corner);
    } else {
        o.pos = lerp(lo, hi, corner);
        float2 t = (o.pos - i.rect.xy) / max(i.rect.zw, float2(1e-6, 1e-6));
        o.uv = lerp(i.bottom.xy, i.bottom.zw, t);
    }
    o.rect = i.rect; o.radii = i.radii; o.top = i.top; o.bottom = i.bottom;
    o.border = i.border; o.params = i.params; o.kind = i.kind; o.clip = i.clip;
    o.position = float4(o.pos.x / view.x * 2.0 - 1.0, 1.0 - o.pos.y / view.y * 2.0, depthOf(i.kind.z), 1.0);
    return o;
}

[[vk::combinedImageSampler]][[vk::binding(0, 2)]]
Texture2D<float4> tex : register(t0, space2);
[[vk::combinedImageSampler]][[vk::binding(0, 2)]]
SamplerState texSampler : register(s0, space2);

[[vk::binding(0, 3)]]
cbuffer FragmentUniforms : register(b0, space3)
{
    float scale;
    float3 fragmentPad;
};

// Signed distance to a rounded box; radii are top-left, top-right, bottom-right, bottom-left.
float roundedBox(float2 p, float2 center, float2 halfSize, float4 r)
{
    float2 q = p - center;
    float radius = q.x < 0.0 ? (q.y < 0.0 ? r.x : r.w) : (q.y < 0.0 ? r.y : r.z);
    radius = min(radius, min(halfSize.x, halfSize.y));
    float2 d = abs(q) - halfSize + radius;
    return length(max(d, 0.0)) + min(max(d.x, d.y), 0.0) - radius;
}

float4 premul(float4 c) { return float4(c.rgb * c.a, c.a); }

float4 PSColor(Varyings v)
{
    if (v.kind.x == 1.0) {
        // A coverage mask (glyph, icon, path) in a color, or (kind.y 1) a canvas drawing
        // already in premultiplied color, faded by the color's alpha.
        float4 t = tex.Sample(texSampler, v.uv);
        return v.kind.y == 1.0 ? t * v.top.a : premul(v.top) * t.r;
    }
    if (v.kind.x == 3.0) {
        // A ground plane seen in perspective ("mode 7"): where this pixel's ray meets the
        // ground, from a camera at radii.xy turned by radii.zw (cos, sin), border.y high,
        // pitched down params.w radians, with the horizon at y border.x and focal length
        // border.z; the texture repeats every border.w units. params: pixel size (blocky),
        // screen center x, haze distance, pitch; bottom.x: roll.
        float px = max(v.params.x, 1e-3);
        float2 p = (floor(v.pos / px) + 0.5) * px;
        float cp = cos(v.params.w), sp = sin(v.params.w);
        // Rolled by bottom.x about the view's center: turn the pixel back first.
        float2 center = float2(v.params.y, v.border.x + v.border.z * sp / cp);
        float2 q = p - center;
        float cr = cos(v.bottom.x), sr = sin(v.bottom.x);
        p = center + float2(cr * q.x + sr * q.y, -sr * q.x + cr * q.y);
        float below = (p.y - v.border.x) * cp;
        if (below <= 0.0) discard;
        float t = v.border.y / below;
        float y = p.y - v.border.x - v.border.z * sp / cp;
        float dist = t * (v.border.z * cp - y * sp);
        float lateral = (p.x - v.params.y) * t;
        float2 forward = float2(v.radii.w, -v.radii.z);
        float2 right = float2(v.radii.z, v.radii.w);
        float2 world = v.radii.xy + forward * dist + right * lateral;
        float lod = log2(max(1.0, px * t * scale * max(1.0, (v.border.z * cp - y * sp) / max(v.border.z, 1e-3))));
        float4 c = premul(tex.SampleLevel(texSampler, world / v.border.w, lod));
        float haze = v.params.z > 0.0 ? saturate(dist / v.params.z) : 0.0;
        return lerp(c, premul(v.top), haze * haze);
    }
    if (v.kind.x == 2.0 && v.kind.y == 1.0) {
        // A transformed image (rotated, flipped): clipped here, as its quad is not upright.
        float2 lo = v.clip.xy, hi = v.clip.xy + v.clip.zw;
        float inside = step(lo.x, v.pos.x) * step(v.pos.x, hi.x) * step(lo.y, v.pos.y) * step(v.pos.y, hi.y);
        return premul(tex.Sample(texSampler, v.uv)) * v.top.a * inside;
    }
    if (v.kind.x == 2.0) {
        // An image: its corners follow the box it fills, and it fades with the top color's alpha.
        float2 bh = v.border.zw * 0.5;
        float d = roundedBox(v.pos, v.border.xy + bh, bh, v.radii);
        return premul(tex.Sample(texSampler, v.uv)) * v.top.a * saturate(0.5 - d * scale);
    }
    float2 halfSize = v.rect.zw * 0.5;
    float d = roundedBox(v.pos, v.rect.xy + halfSize, halfSize, v.radii);
    float blur = v.params.y;
    if (blur > 0.0) {
        // A shadow is not drawn under its element: the element is the shadow moved back by its offset and spread.
        float spread = v.params.z;
        float2 holeHalf = max(halfSize - spread, float2(0.0, 0.0));
        float2 holeCenter = v.rect.xy + halfSize - float2(0.0, v.params.w);
        float hole = roundedBox(v.pos, holeCenter, holeHalf, max(v.radii - spread, float4(0.0, 0.0, 0.0, 0.0)));
        float outside = saturate(0.5 + hole * scale);
        return premul(v.top) * (1.0 - smoothstep(-0.5 * blur, 0.5 * blur, d)) * outside;
    }
    float outer = saturate(0.5 - d * scale);
    float t = saturate((v.pos.y - v.rect.y) / max(v.rect.w, 1.0));
    float4 fill = premul(lerp(v.top, v.bottom, t)) * outer;
    float bw = v.params.x;
    if (bw > 0.0) {
        float inner = saturate(0.5 - (d + bw) * scale);
        float4 band = premul(v.border) * (outer - inner);
        fill = band + fill * (1.0 - band.a);
    }
    return fill;
}

// Terrain from a height map, ray marched ("voxel space"): each pixel's ray steps out from
// the camera over the map (colors in rgb, heights in alpha) until it is under the ground,
// then closes in on the crossing. radii: camera x, y, cos and sin of its yaw; top: fog;
// bottom: roll, view center y, camera height, height scale; border: view center x, focal
// length, map size in units, fog distance; params: pixel size, pitch, time, how far.
float4 terrain(Varyings v, out float depth)
{
    float px = max(v.params.x, 1e-3);
    float2 p = (floor(v.pos / px) + 0.5) * px;
    float2 q = p - float2(v.border.x, v.bottom.y);
    float cr = cos(v.bottom.x), sr = sin(v.bottom.x);
    q = float2(cr * q.x + sr * q.y, -sr * q.x + cr * q.y);
    float f = v.border.y;
    float cp = cos(v.params.y), sp = sin(v.params.y);
    float ahead = f * cp - q.y * sp;
    float rise = -f * sp - q.y * cp;
    float2 fwd = float2(v.radii.w, -v.radii.z);
    float2 right = float2(v.radii.z, v.radii.w);
    float len = length(float3(ahead, rise, q.x));
    float3 dir = float3(fwd * ahead + right * q.x, rise) / len;
    float3 cam = float3(v.radii.xy, v.bottom.z);
    float top = v.bottom.w, size = v.border.z, far = v.params.w;
    // Looking up from above the highest hill: sky.
    if (dir.z >= 0.0 && cam.z >= top) discard;
    float texels = v.kind.y / size;
    float t = 1.0, last = 0.0;
    bool hit = false;
    [loop] for (int i = 0; i < 300; i++) {
        float3 w = cam + dir * t;
        if (dir.z >= 0.0 && w.z > top) break;
        float lod = log2(max(1.0, t * px / f * texels));
        float h = tex.SampleLevel(texSampler, w.xy / size, lod).a * top;
        if (w.z <= h) { hit = true; break; }
        last = t;
        t += max(0.4, t * 0.02);
        if (t > far) break;
    }
    if (!hit) discard;
    float lo = last, hi = t;
    [unroll] for (int k = 0; k < 6; k++) {
        float m = (lo + hi) * 0.5;
        float3 w = cam + dir * m;
        float lod = log2(max(1.0, m * px / f * texels));
        if (w.z <= tex.SampleLevel(texSampler, w.xy / size, lod).a * top) hi = m; else lo = m;
    }
    float3 w = cam + dir * hi;
    float4 c = tex.SampleLevel(texSampler, w.xy / size, log2(max(1.0, hi * px / f * texels)));
    float3 color = c.rgb;
    if (c.a < 0.004) {
        // The sea: slow swells, and glints where they crest.
        float s = sin(w.x * 0.11 + v.params.z * 1.1) * sin(w.y * 0.09 - v.params.z * 0.8)
                + 0.5 * sin((w.x - w.y) * 0.23 + v.params.z * 1.9);
        float near = saturate(1.0 - hi / 700.0);
        color *= 1.0 + 0.05 * s * near;
        color += pow(saturate(s * 0.7), 8.0) * 0.3 * near;
    }
    float haze = v.border.w > 0.0 ? saturate(hi / v.border.w) : 0.0;
    color = lerp(color, v.top.rgb, haze * haze);
    depth = depthOf(hi * f / len);
    return float4(color, 1.0);
}

struct Pixel
{
    float4 color : SV_Target0;
    float depth : SV_Depth;
};

Pixel PSMain(Varyings v)
{
    Pixel o;
    if (v.kind.x == 4.0) {
        o.color = terrain(v, o.depth);
        return o;
    }
    o.color = PSColor(v);
    // Drawn at a depth (3D scenes): see-through pixels leave the depth alone.
    if (v.kind.z > 0.0 && o.color.a < 0.004) discard;
    o.depth = v.position.z;
    return o;
}
