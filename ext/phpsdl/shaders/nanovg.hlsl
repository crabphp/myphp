// NanoVG's shader for SDL_GPU (nanovg_sdlgpu.c): the GL3 backend's fill shader with
// edge antialiasing, uniforms as its 11-vec4 array. Compiled by build.sh.

struct Vertex
{
    float2 vertex : TEXCOORD0;
    float2 tcoord : TEXCOORD1;
};

struct Varyings
{
    float4 position : SV_Position;
    float2 ftcoord : TEXCOORD0;
    float2 fpos : TEXCOORD1;
};

[[vk::binding(0, 1)]]
cbuffer VertexUniforms : register(b0, space1)
{
    float2 viewSize;
    float2 vertexPad;
};

Varyings VSMain(Vertex i)
{
    Varyings o;
    o.ftcoord = i.tcoord;
    o.fpos = i.vertex;
    o.position = float4(2.0 * i.vertex.x / viewSize.x - 1.0, 1.0 - 2.0 * i.vertex.y / viewSize.y, 0.0, 1.0);
    return o;
}

[[vk::combinedImageSampler]][[vk::binding(0, 2)]]
Texture2D<float4> tex : register(t0, space2);
[[vk::combinedImageSampler]][[vk::binding(0, 2)]]
SamplerState texSampler : register(s0, space2);

[[vk::binding(0, 3)]]
cbuffer FragmentUniforms : register(b0, space3)
{
    // scissorMat (3 columns), paintMat (3 columns), innerCol, outerCol,
    // scissorExt + scissorScale, extent + radius + feather,
    // strokeMult + strokeThr + texType + type
    float4 frag[11];
};

// The matrices are mat3 columns; only x and y of the product are used.
float2 scissorXform(float2 p) { return frag[0].xy * p.x + frag[1].xy * p.y + frag[2].xy; }
float2 paintXform(float2 p) { return frag[3].xy * p.x + frag[4].xy * p.y + frag[5].xy; }

float sdroundrect(float2 pt, float2 ext, float rad)
{
    float2 ext2 = ext - float2(rad, rad);
    float2 d = abs(pt) - ext2;
    return min(max(d.x, d.y), 0.0) + length(max(d, 0.0)) - rad;
}

float scissorMask(float2 p)
{
    float2 sc = abs(scissorXform(p)) - frag[8].xy;
    sc = float2(0.5, 0.5) - sc * frag[8].zw;
    return clamp(sc.x, 0.0, 1.0) * clamp(sc.y, 0.0, 1.0);
}

float4 PSMain(Varyings v) : SV_Target0
{
    float4 innerCol = frag[6];
    float4 outerCol = frag[7];
    float2 extent = frag[9].xy;
    float radius = frag[9].z;
    float feather = frag[9].w;
    float strokeMult = frag[10].x;
    float strokeThr = frag[10].y;
    int texType = (int) frag[10].z;
    int type = (int) frag[10].w;

    float scissor = scissorMask(v.fpos);
    // Stroke: from [0..1] to a clipped pyramid, where the slope is 1px.
    float strokeAlpha = min(1.0, (1.0 - abs(v.ftcoord.x * 2.0 - 1.0)) * strokeMult) * min(1.0, v.ftcoord.y);
    if (strokeAlpha < strokeThr) discard;

    float4 result = float4(0, 0, 0, 0);
    if (type == 0) {
        // Gradient
        float2 pt = paintXform(v.fpos);
        float d = clamp((sdroundrect(pt, extent, radius) + feather * 0.5) / feather, 0.0, 1.0);
        result = lerp(innerCol, outerCol, d) * strokeAlpha * scissor;
    } else if (type == 1) {
        // Image
        float2 pt = paintXform(v.fpos) / extent;
        float4 color = tex.Sample(texSampler, pt);
        if (texType == 1) color = float4(color.xyz * color.w, color.w);
        if (texType == 2) color = color.xxxx;
        result = color * innerCol * strokeAlpha * scissor;
    } else if (type == 2) {
        // Stencil fill
        result = float4(1, 1, 1, 1);
    } else if (type == 3) {
        // Textured triangles
        float4 color = tex.Sample(texSampler, v.ftcoord);
        if (texType == 1) color = float4(color.xyz * color.w, color.w);
        if (texType == 2) color = color.xxxx;
        result = color * scissor * innerCol;
    }
    return result;
}
