/**
 * PHP-glfw 
 * 
 * Extension constants
 *
 * Copyright (c) 2018-2024 Mario Döring
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
#ifndef PHP_GLFW_CONSTANTS_H
#define PHP_GLFW_CONSTANTS_H 1

#ifndef _GLFW_WIN32
#ifdef _WIN32
#define _GLFW_WIN32
#endif
#endif

#include <zend_API.h>
#include "phpsdl_gltypes.h"


/**
 
 * PHPGlfw Extension 
 * ----------------------------------------------------------------------------
 *  
 */

/**
 
 * AttribMask 
 * ----------------------------------------------------------------------------
 *  
 */
 
#ifdef GL_DEPTH_BUFFER_BIT 
#endif
 
#ifdef GL_STENCIL_BUFFER_BIT 
#endif
 
#ifdef GL_COLOR_BUFFER_BIT 
#endif
 
#ifdef GL_CURRENT_BIT 
#endif
 
#ifdef GL_POINT_BIT 
#endif
 
#ifdef GL_LINE_BIT 
#endif
 
#ifdef GL_POLYGON_BIT 
#endif
 
#ifdef GL_POLYGON_STIPPLE_BIT 
#endif
 
#ifdef GL_PIXEL_MODE_BIT 
#endif
 
#ifdef GL_LIGHTING_BIT 
#endif
 
#ifdef GL_FOG_BIT 
#endif
 
#ifdef GL_ACCUM_BUFFER_BIT 
#endif
 
#ifdef GL_VIEWPORT_BIT 
#endif
 
#ifdef GL_TRANSFORM_BIT 
#endif
 
#ifdef GL_ENABLE_BIT 
#endif
 
#ifdef GL_HINT_BIT 
#endif
 
#ifdef GL_EVAL_BIT 
#endif
 
#ifdef GL_LIST_BIT 
#endif
 
#ifdef GL_TEXTURE_BIT 
#endif
 
#ifdef GL_SCISSOR_BIT 
#endif
 
#ifdef GL_ALL_ATTRIB_BITS 
#endif
 
#ifdef GL_MULTISAMPLE_BIT 
#endif

/**
 
 * SpecialNumbers 
 * ----------------------------------------------------------------------------
 * @vendor ARB
 * Tokens whose numeric value is intrinsically meaningful 
 */
 
#ifdef GL_ZERO 
#endif
 
#ifdef GL_ONE 
#endif
 
#ifdef GL_NONE 
#endif
 
#ifdef GL_NO_ERROR 
#endif
 
#ifdef GL_INVALID_INDEX 
#endif
 
#ifdef GL_TIMEOUT_IGNORED 
#endif

/**
 * @vendor ARB
 * Mostly OpenGL 1.0/1.1 enum assignments. Unused ranges should generally remain
 * unused. 
 */
 
#ifdef GL_POINTS 
#endif
 
#ifdef GL_LINES 
#endif
 
#ifdef GL_LINE_LOOP 
#endif
 
#ifdef GL_LINE_STRIP 
#endif
 
#ifdef GL_TRIANGLES 
#endif
 
#ifdef GL_TRIANGLE_STRIP 
#endif
 
#ifdef GL_TRIANGLE_FAN 
#endif
 
#ifdef GL_QUADS 
#endif
 
#ifdef GL_NEVER 
#endif
 
#ifdef GL_LESS 
#endif
 
#ifdef GL_EQUAL 
#endif
 
#ifdef GL_LEQUAL 
#endif
 
#ifdef GL_GREATER 
#endif
 
#ifdef GL_NOTEQUAL 
#endif
 
#ifdef GL_GEQUAL 
#endif
 
#ifdef GL_ALWAYS 
#endif
 
#ifdef GL_SRC_COLOR 
#endif
 
#ifdef GL_ONE_MINUS_SRC_COLOR 
#endif
 
#ifdef GL_SRC_ALPHA 
#endif
 
#ifdef GL_ONE_MINUS_SRC_ALPHA 
#endif
 
#ifdef GL_DST_ALPHA 
#endif
 
#ifdef GL_ONE_MINUS_DST_ALPHA 
#endif
 
#ifdef GL_DST_COLOR 
#endif
 
#ifdef GL_ONE_MINUS_DST_COLOR 
#endif
 
#ifdef GL_SRC_ALPHA_SATURATE 
#endif
 
#ifdef GL_FRONT_LEFT 
#endif
 
#ifdef GL_FRONT_RIGHT 
#endif
 
#ifdef GL_BACK_LEFT 
#endif
 
#ifdef GL_BACK_RIGHT 
#endif
 
#ifdef GL_FRONT 
#endif
 
#ifdef GL_BACK 
#endif
 
#ifdef GL_LEFT 
#endif
 
#ifdef GL_RIGHT 
#endif
 
#ifdef GL_FRONT_AND_BACK 
#endif
 
#ifdef GL_INVALID_ENUM 
#endif
 
#ifdef GL_INVALID_VALUE 
#endif
 
#ifdef GL_INVALID_OPERATION 
#endif
 
#ifdef GL_OUT_OF_MEMORY 
#endif
 
#ifdef GL_CW 
#endif
 
#ifdef GL_CCW 
#endif
 
#ifdef GL_POINT_SIZE 
#endif
 
#ifdef GL_POINT_SIZE_RANGE 
#endif
 
#ifdef GL_POINT_SIZE_GRANULARITY 
#endif
 
#ifdef GL_LINE_SMOOTH 
#endif
 
#ifdef GL_LINE_WIDTH 
#endif
 
#ifdef GL_LINE_WIDTH_RANGE 
#endif
 
#ifdef GL_LINE_WIDTH_GRANULARITY 
#endif
 
#ifdef GL_POLYGON_MODE 
#endif
 
#ifdef GL_POLYGON_SMOOTH 
#endif
 
#ifdef GL_CULL_FACE 
#endif
 
#ifdef GL_CULL_FACE_MODE 
#endif
 
#ifdef GL_FRONT_FACE 
#endif
 
#ifdef GL_DEPTH_RANGE 
#endif
 
#ifdef GL_DEPTH_TEST 
#endif
 
#ifdef GL_DEPTH_WRITEMASK 
#endif
 
#ifdef GL_DEPTH_CLEAR_VALUE 
#endif
 
#ifdef GL_DEPTH_FUNC 
#endif
 
#ifdef GL_STENCIL_TEST 
#endif
 
#ifdef GL_STENCIL_CLEAR_VALUE 
#endif
 
#ifdef GL_STENCIL_FUNC 
#endif
 
#ifdef GL_STENCIL_VALUE_MASK 
#endif
 
#ifdef GL_STENCIL_FAIL 
#endif
 
#ifdef GL_STENCIL_PASS_DEPTH_FAIL 
#endif
 
#ifdef GL_STENCIL_PASS_DEPTH_PASS 
#endif
 
#ifdef GL_STENCIL_REF 
#endif
 
#ifdef GL_STENCIL_WRITEMASK 
#endif
 
#ifdef GL_VIEWPORT 
#endif
 
#ifdef GL_DITHER 
#endif
 
#ifdef GL_BLEND_DST 
#endif
 
#ifdef GL_BLEND_SRC 
#endif
 
#ifdef GL_BLEND 
#endif
 
#ifdef GL_LOGIC_OP_MODE 
#endif
 
#ifdef GL_DRAW_BUFFER 
#endif
 
#ifdef GL_READ_BUFFER 
#endif
 
#ifdef GL_SCISSOR_BOX 
#endif
 
#ifdef GL_SCISSOR_TEST 
#endif
 
#ifdef GL_COLOR_CLEAR_VALUE 
#endif
 
#ifdef GL_COLOR_WRITEMASK 
#endif
 
#ifdef GL_DOUBLEBUFFER 
#endif
 
#ifdef GL_STEREO 
#endif
 
#ifdef GL_LINE_SMOOTH_HINT 
#endif
 
#ifdef GL_POLYGON_SMOOTH_HINT 
#endif
 
#ifdef GL_UNPACK_SWAP_BYTES 
#endif
 
#ifdef GL_UNPACK_LSB_FIRST 
#endif
 
#ifdef GL_UNPACK_ROW_LENGTH 
#endif
 
#ifdef GL_UNPACK_SKIP_ROWS 
#endif
 
#ifdef GL_UNPACK_SKIP_PIXELS 
#endif
 
#ifdef GL_UNPACK_ALIGNMENT 
#endif
 
#ifdef GL_PACK_SWAP_BYTES 
#endif
 
#ifdef GL_PACK_LSB_FIRST 
#endif
 
#ifdef GL_PACK_ROW_LENGTH 
#endif
 
#ifdef GL_PACK_SKIP_ROWS 
#endif
 
#ifdef GL_PACK_SKIP_PIXELS 
#endif
 
#ifdef GL_PACK_ALIGNMENT 
#endif
 
#ifdef GL_MAX_TEXTURE_SIZE 
#endif
 
#ifdef GL_MAX_VIEWPORT_DIMS 
#endif
 
#ifdef GL_SUBPIXEL_BITS 
#endif
 
#ifdef GL_TEXTURE_1D 
#endif
 
#ifdef GL_TEXTURE_2D 
#endif
 
#ifdef GL_TEXTURE_WIDTH 
#endif
 
#ifdef GL_TEXTURE_HEIGHT 
#endif
 
#ifdef GL_TEXTURE_BORDER_COLOR 
#endif
 
#ifdef GL_DONT_CARE 
#endif
 
#ifdef GL_FASTEST 
#endif
 
#ifdef GL_NICEST 
#endif
 
#ifdef GL_BYTE 
#endif
 
#ifdef GL_UNSIGNED_BYTE 
#endif
 
#ifdef GL_SHORT 
#endif
 
#ifdef GL_UNSIGNED_SHORT 
#endif
 
#ifdef GL_INT 
#endif
 
#ifdef GL_UNSIGNED_INT 
#endif
 
#ifdef GL_FLOAT 
#endif
 
#ifdef GL_STACK_OVERFLOW 
#endif
 
#ifdef GL_STACK_UNDERFLOW 
#endif
 
#ifdef GL_CLEAR 
#endif
 
#ifdef GL_AND 
#endif
 
#ifdef GL_AND_REVERSE 
#endif
 
#ifdef GL_COPY 
#endif
 
#ifdef GL_AND_INVERTED 
#endif
 
#ifdef GL_NOOP 
#endif
 
#ifdef GL_XOR 
#endif
 
#ifdef GL_OR 
#endif
 
#ifdef GL_NOR 
#endif
 
#ifdef GL_EQUIV 
#endif
 
#ifdef GL_INVERT 
#endif
 
#ifdef GL_OR_REVERSE 
#endif
 
#ifdef GL_COPY_INVERTED 
#endif
 
#ifdef GL_OR_INVERTED 
#endif
 
#ifdef GL_NAND 
#endif
 
#ifdef GL_SET 
#endif
 
#ifdef GL_TEXTURE 
#endif
 
#ifdef GL_COLOR 
#endif
 
#ifdef GL_DEPTH 
#endif
 
#ifdef GL_STENCIL 
#endif
 
#ifdef GL_STENCIL_INDEX 
#endif
 
#ifdef GL_DEPTH_COMPONENT 
#endif
 
#ifdef GL_RED 
#endif
 
#ifdef GL_GREEN 
#endif
 
#ifdef GL_BLUE 
#endif
 
#ifdef GL_ALPHA 
#endif
 
#ifdef GL_RGB 
#endif
 
#ifdef GL_RGBA 
#endif
 
#ifdef GL_POINT 
#endif
 
#ifdef GL_LINE 
#endif
 
#ifdef GL_FILL 
#endif
 
#ifdef GL_KEEP 
#endif
 
#ifdef GL_REPLACE 
#endif
 
#ifdef GL_INCR 
#endif
 
#ifdef GL_DECR 
#endif
 
#ifdef GL_VENDOR 
#endif
 
#ifdef GL_RENDERER 
#endif
 
#ifdef GL_VERSION 
#endif
 
#ifdef GL_EXTENSIONS 
#endif
 
#ifdef GL_NEAREST 
#endif
 
#ifdef GL_LINEAR 
#endif
 
#ifdef GL_NEAREST_MIPMAP_NEAREST 
#endif
 
#ifdef GL_LINEAR_MIPMAP_NEAREST 
#endif
 
#ifdef GL_NEAREST_MIPMAP_LINEAR 
#endif
 
#ifdef GL_LINEAR_MIPMAP_LINEAR 
#endif
 
#ifdef GL_TEXTURE_MAG_FILTER 
#endif
 
#ifdef GL_TEXTURE_MIN_FILTER 
#endif
 
#ifdef GL_TEXTURE_WRAP_S 
#endif
 
#ifdef GL_TEXTURE_WRAP_T 
#endif
 
#ifdef GL_REPEAT 
#endif
 
#ifdef GL_QUAD_STRIP 
#endif
 
#ifdef GL_POLYGON 
#endif
 
#ifdef GL_ACCUM 
#endif
 
#ifdef GL_LOAD 
#endif
 
#ifdef GL_RETURN 
#endif
 
#ifdef GL_MULT 
#endif
 
#ifdef GL_ADD 
#endif
 
#ifdef GL_AUX0 
#endif
 
#ifdef GL_AUX1 
#endif
 
#ifdef GL_AUX2 
#endif
 
#ifdef GL_AUX3 
#endif
 
#ifdef GL_2D 
#endif
 
#ifdef GL_3D 
#endif
 
#ifdef GL_3D_COLOR 
#endif
 
#ifdef GL_3D_COLOR_TEXTURE 
#endif
 
#ifdef GL_4D_COLOR_TEXTURE 
#endif
 
#ifdef GL_PASS_THROUGH_TOKEN 
#endif
 
#ifdef GL_POINT_TOKEN 
#endif
 
#ifdef GL_LINE_TOKEN 
#endif
 
#ifdef GL_POLYGON_TOKEN 
#endif
 
#ifdef GL_BITMAP_TOKEN 
#endif
 
#ifdef GL_DRAW_PIXEL_TOKEN 
#endif
 
#ifdef GL_COPY_PIXEL_TOKEN 
#endif
 
#ifdef GL_LINE_RESET_TOKEN 
#endif
 
#ifdef GL_EXP 
#endif
 
#ifdef GL_EXP2 
#endif
 
#ifdef GL_COEFF 
#endif
 
#ifdef GL_ORDER 
#endif
 
#ifdef GL_DOMAIN 
#endif
 
#ifdef GL_PIXEL_MAP_I_TO_I 
#endif
 
#ifdef GL_PIXEL_MAP_S_TO_S 
#endif
 
#ifdef GL_PIXEL_MAP_I_TO_R 
#endif
 
#ifdef GL_PIXEL_MAP_I_TO_G 
#endif
 
#ifdef GL_PIXEL_MAP_I_TO_B 
#endif
 
#ifdef GL_PIXEL_MAP_I_TO_A 
#endif
 
#ifdef GL_PIXEL_MAP_R_TO_R 
#endif
 
#ifdef GL_PIXEL_MAP_G_TO_G 
#endif
 
#ifdef GL_PIXEL_MAP_B_TO_B 
#endif
 
#ifdef GL_PIXEL_MAP_A_TO_A 
#endif
 
#ifdef GL_CURRENT_COLOR 
#endif
 
#ifdef GL_CURRENT_INDEX 
#endif
 
#ifdef GL_CURRENT_NORMAL 
#endif
 
#ifdef GL_CURRENT_TEXTURE_COORDS 
#endif
 
#ifdef GL_CURRENT_RASTER_COLOR 
#endif
 
#ifdef GL_CURRENT_RASTER_INDEX 
#endif
 
#ifdef GL_CURRENT_RASTER_TEXTURE_COORDS 
#endif
 
#ifdef GL_CURRENT_RASTER_POSITION 
#endif
 
#ifdef GL_CURRENT_RASTER_POSITION_VALID 
#endif
 
#ifdef GL_CURRENT_RASTER_DISTANCE 
#endif
 
#ifdef GL_POINT_SMOOTH 
#endif
 
#ifdef GL_LINE_STIPPLE 
#endif
 
#ifdef GL_LINE_STIPPLE_PATTERN 
#endif
 
#ifdef GL_LINE_STIPPLE_REPEAT 
#endif
 
#ifdef GL_LIST_MODE 
#endif
 
#ifdef GL_MAX_LIST_NESTING 
#endif
 
#ifdef GL_LIST_BASE 
#endif
 
#ifdef GL_LIST_INDEX 
#endif
 
#ifdef GL_POLYGON_STIPPLE 
#endif
 
#ifdef GL_EDGE_FLAG 
#endif
 
#ifdef GL_LIGHTING 
#endif
 
#ifdef GL_LIGHT_MODEL_LOCAL_VIEWER 
#endif
 
#ifdef GL_LIGHT_MODEL_TWO_SIDE 
#endif
 
#ifdef GL_LIGHT_MODEL_AMBIENT 
#endif
 
#ifdef GL_SHADE_MODEL 
#endif
 
#ifdef GL_COLOR_MATERIAL_FACE 
#endif
 
#ifdef GL_COLOR_MATERIAL_PARAMETER 
#endif
 
#ifdef GL_COLOR_MATERIAL 
#endif
 
#ifdef GL_FOG 
#endif
 
#ifdef GL_FOG_INDEX 
#endif
 
#ifdef GL_FOG_DENSITY 
#endif
 
#ifdef GL_FOG_START 
#endif
 
#ifdef GL_FOG_END 
#endif
 
#ifdef GL_FOG_MODE 
#endif
 
#ifdef GL_FOG_COLOR 
#endif
 
#ifdef GL_ACCUM_CLEAR_VALUE 
#endif
 
#ifdef GL_MATRIX_MODE 
#endif
 
#ifdef GL_NORMALIZE 
#endif
 
#ifdef GL_MODELVIEW_STACK_DEPTH 
#endif
 
#ifdef GL_PROJECTION_STACK_DEPTH 
#endif
 
#ifdef GL_TEXTURE_STACK_DEPTH 
#endif
 
#ifdef GL_MODELVIEW_MATRIX 
#endif
 
#ifdef GL_PROJECTION_MATRIX 
#endif
 
#ifdef GL_TEXTURE_MATRIX 
#endif
 
#ifdef GL_ATTRIB_STACK_DEPTH 
#endif
 
#ifdef GL_ALPHA_TEST 
#endif
 
#ifdef GL_ALPHA_TEST_FUNC 
#endif
 
#ifdef GL_ALPHA_TEST_REF 
#endif
 
#ifdef GL_LOGIC_OP 
#endif
 
#ifdef GL_AUX_BUFFERS 
#endif
 
#ifdef GL_INDEX_CLEAR_VALUE 
#endif
 
#ifdef GL_INDEX_WRITEMASK 
#endif
 
#ifdef GL_INDEX_MODE 
#endif
 
#ifdef GL_RGBA_MODE 
#endif
 
#ifdef GL_RENDER_MODE 
#endif
 
#ifdef GL_PERSPECTIVE_CORRECTION_HINT 
#endif
 
#ifdef GL_POINT_SMOOTH_HINT 
#endif
 
#ifdef GL_FOG_HINT 
#endif
 
#ifdef GL_TEXTURE_GEN_S 
#endif
 
#ifdef GL_TEXTURE_GEN_T 
#endif
 
#ifdef GL_TEXTURE_GEN_R 
#endif
 
#ifdef GL_TEXTURE_GEN_Q 
#endif
 
#ifdef GL_PIXEL_MAP_I_TO_I_SIZE 
#endif
 
#ifdef GL_PIXEL_MAP_S_TO_S_SIZE 
#endif
 
#ifdef GL_PIXEL_MAP_I_TO_R_SIZE 
#endif
 
#ifdef GL_PIXEL_MAP_I_TO_G_SIZE 
#endif
 
#ifdef GL_PIXEL_MAP_I_TO_B_SIZE 
#endif
 
#ifdef GL_PIXEL_MAP_I_TO_A_SIZE 
#endif
 
#ifdef GL_PIXEL_MAP_R_TO_R_SIZE 
#endif
 
#ifdef GL_PIXEL_MAP_G_TO_G_SIZE 
#endif
 
#ifdef GL_PIXEL_MAP_B_TO_B_SIZE 
#endif
 
#ifdef GL_PIXEL_MAP_A_TO_A_SIZE 
#endif
 
#ifdef GL_MAP_COLOR 
#endif
 
#ifdef GL_MAP_STENCIL 
#endif
 
#ifdef GL_INDEX_SHIFT 
#endif
 
#ifdef GL_INDEX_OFFSET 
#endif
 
#ifdef GL_RED_SCALE 
#endif
 
#ifdef GL_RED_BIAS 
#endif
 
#ifdef GL_ZOOM_X 
#endif
 
#ifdef GL_ZOOM_Y 
#endif
 
#ifdef GL_GREEN_SCALE 
#endif
 
#ifdef GL_GREEN_BIAS 
#endif
 
#ifdef GL_BLUE_SCALE 
#endif
 
#ifdef GL_BLUE_BIAS 
#endif
 
#ifdef GL_ALPHA_SCALE 
#endif
 
#ifdef GL_ALPHA_BIAS 
#endif
 
#ifdef GL_DEPTH_SCALE 
#endif
 
#ifdef GL_DEPTH_BIAS 
#endif
 
#ifdef GL_MAX_EVAL_ORDER 
#endif
 
#ifdef GL_MAX_LIGHTS 
#endif
 
#ifdef GL_MAX_CLIP_PLANES 
#endif
 
#ifdef GL_MAX_PIXEL_MAP_TABLE 
#endif
 
#ifdef GL_MAX_ATTRIB_STACK_DEPTH 
#endif
 
#ifdef GL_MAX_MODELVIEW_STACK_DEPTH 
#endif
 
#ifdef GL_MAX_NAME_STACK_DEPTH 
#endif
 
#ifdef GL_MAX_PROJECTION_STACK_DEPTH 
#endif
 
#ifdef GL_MAX_TEXTURE_STACK_DEPTH 
#endif
 
#ifdef GL_INDEX_BITS 
#endif
 
#ifdef GL_RED_BITS 
#endif
 
#ifdef GL_GREEN_BITS 
#endif
 
#ifdef GL_BLUE_BITS 
#endif
 
#ifdef GL_ALPHA_BITS 
#endif
 
#ifdef GL_DEPTH_BITS 
#endif
 
#ifdef GL_STENCIL_BITS 
#endif
 
#ifdef GL_ACCUM_RED_BITS 
#endif
 
#ifdef GL_ACCUM_GREEN_BITS 
#endif
 
#ifdef GL_ACCUM_BLUE_BITS 
#endif
 
#ifdef GL_ACCUM_ALPHA_BITS 
#endif
 
#ifdef GL_NAME_STACK_DEPTH 
#endif
 
#ifdef GL_AUTO_NORMAL 
#endif
 
#ifdef GL_MAP1_COLOR_4 
#endif
 
#ifdef GL_MAP1_INDEX 
#endif
 
#ifdef GL_MAP1_NORMAL 
#endif
 
#ifdef GL_MAP1_TEXTURE_COORD_1 
#endif
 
#ifdef GL_MAP1_TEXTURE_COORD_2 
#endif
 
#ifdef GL_MAP1_TEXTURE_COORD_3 
#endif
 
#ifdef GL_MAP1_TEXTURE_COORD_4 
#endif
 
#ifdef GL_MAP1_VERTEX_3 
#endif
 
#ifdef GL_MAP1_VERTEX_4 
#endif
 
#ifdef GL_MAP2_COLOR_4 
#endif
 
#ifdef GL_MAP2_INDEX 
#endif
 
#ifdef GL_MAP2_NORMAL 
#endif
 
#ifdef GL_MAP2_TEXTURE_COORD_1 
#endif
 
#ifdef GL_MAP2_TEXTURE_COORD_2 
#endif
 
#ifdef GL_MAP2_TEXTURE_COORD_3 
#endif
 
#ifdef GL_MAP2_TEXTURE_COORD_4 
#endif
 
#ifdef GL_MAP2_VERTEX_3 
#endif
 
#ifdef GL_MAP2_VERTEX_4 
#endif
 
#ifdef GL_MAP1_GRID_DOMAIN 
#endif
 
#ifdef GL_MAP1_GRID_SEGMENTS 
#endif
 
#ifdef GL_MAP2_GRID_DOMAIN 
#endif
 
#ifdef GL_MAP2_GRID_SEGMENTS 
#endif
 
#ifdef GL_TEXTURE_COMPONENTS 
#endif
 
#ifdef GL_TEXTURE_BORDER 
#endif
 
#ifdef GL_AMBIENT 
#endif
 
#ifdef GL_DIFFUSE 
#endif
 
#ifdef GL_SPECULAR 
#endif
 
#ifdef GL_POSITION 
#endif
 
#ifdef GL_SPOT_DIRECTION 
#endif
 
#ifdef GL_SPOT_EXPONENT 
#endif
 
#ifdef GL_SPOT_CUTOFF 
#endif
 
#ifdef GL_CONSTANT_ATTENUATION 
#endif
 
#ifdef GL_LINEAR_ATTENUATION 
#endif
 
#ifdef GL_QUADRATIC_ATTENUATION 
#endif
 
#ifdef GL_COMPILE 
#endif
 
#ifdef GL_COMPILE_AND_EXECUTE 
#endif
 
#ifdef GL_2_BYTES 
#endif
 
#ifdef GL_3_BYTES 
#endif
 
#ifdef GL_4_BYTES 
#endif
 
#ifdef GL_EMISSION 
#endif
 
#ifdef GL_SHININESS 
#endif
 
#ifdef GL_AMBIENT_AND_DIFFUSE 
#endif
 
#ifdef GL_COLOR_INDEXES 
#endif
 
#ifdef GL_MODELVIEW 
#endif
 
#ifdef GL_PROJECTION 
#endif
 
#ifdef GL_COLOR_INDEX 
#endif
 
#ifdef GL_LUMINANCE 
#endif
 
#ifdef GL_LUMINANCE_ALPHA 
#endif
 
#ifdef GL_BITMAP 
#endif
 
#ifdef GL_RENDER 
#endif
 
#ifdef GL_FEEDBACK 
#endif
 
#ifdef GL_SELECT 
#endif
 
#ifdef GL_FLAT 
#endif
 
#ifdef GL_SMOOTH 
#endif
 
#ifdef GL_S 
#endif
 
#ifdef GL_T 
#endif
 
#ifdef GL_R 
#endif
 
#ifdef GL_Q 
#endif
 
#ifdef GL_MODULATE 
#endif
 
#ifdef GL_DECAL 
#endif
 
#ifdef GL_TEXTURE_ENV_MODE 
#endif
 
#ifdef GL_TEXTURE_ENV_COLOR 
#endif
 
#ifdef GL_TEXTURE_ENV 
#endif
 
#ifdef GL_EYE_LINEAR 
#endif
 
#ifdef GL_OBJECT_LINEAR 
#endif
 
#ifdef GL_SPHERE_MAP 
#endif
 
#ifdef GL_TEXTURE_GEN_MODE 
#endif
 
#ifdef GL_OBJECT_PLANE 
#endif
 
#ifdef GL_EYE_PLANE 
#endif
 
#ifdef GL_CLAMP 
#endif
 
#ifdef GL_CLIP_PLANE0 
#endif
 
#ifdef GL_CLIP_PLANE1 
#endif
 
#ifdef GL_CLIP_PLANE2 
#endif
 
#ifdef GL_CLIP_PLANE3 
#endif
 
#ifdef GL_CLIP_PLANE4 
#endif
 
#ifdef GL_CLIP_PLANE5 
#endif
 
#ifdef GL_LIGHT0 
#endif
 
#ifdef GL_LIGHT1 
#endif
 
#ifdef GL_LIGHT2 
#endif
 
#ifdef GL_LIGHT3 
#endif
 
#ifdef GL_LIGHT4 
#endif
 
#ifdef GL_LIGHT5 
#endif
 
#ifdef GL_LIGHT6 
#endif
 
#ifdef GL_LIGHT7 
#endif
 
#ifdef GL_COLOR_LOGIC_OP 
#endif
 
#ifdef GL_POLYGON_OFFSET_UNITS 
#endif
 
#ifdef GL_POLYGON_OFFSET_POINT 
#endif
 
#ifdef GL_POLYGON_OFFSET_LINE 
#endif
 
#ifdef GL_TEXTURE_INTERNAL_FORMAT 
#endif
 
#ifdef GL_DOUBLE 
#endif
 
#ifdef GL_R3_G3_B2 
#endif
 
#ifdef GL_FEEDBACK_BUFFER_POINTER 
#endif
 
#ifdef GL_SELECTION_BUFFER_POINTER 
#endif
 
#ifdef GL_CLIENT_ATTRIB_STACK_DEPTH 
#endif
 
#ifdef GL_INDEX_LOGIC_OP 
#endif
 
#ifdef GL_MAX_CLIENT_ATTRIB_STACK_DEPTH 
#endif
 
#ifdef GL_FEEDBACK_BUFFER_SIZE 
#endif
 
#ifdef GL_FEEDBACK_BUFFER_TYPE 
#endif
 
#ifdef GL_SELECTION_BUFFER_SIZE 
#endif
 
#ifdef GL_V2F 
#endif
 
#ifdef GL_V3F 
#endif
 
#ifdef GL_C4UB_V2F 
#endif
 
#ifdef GL_C4UB_V3F 
#endif
 
#ifdef GL_C3F_V3F 
#endif
 
#ifdef GL_N3F_V3F 
#endif
 
#ifdef GL_C4F_N3F_V3F 
#endif
 
#ifdef GL_T2F_V3F 
#endif
 
#ifdef GL_T4F_V4F 
#endif
 
#ifdef GL_T2F_C4UB_V3F 
#endif
 
#ifdef GL_T2F_C3F_V3F 
#endif
 
#ifdef GL_T2F_N3F_V3F 
#endif
 
#ifdef GL_T2F_C4F_N3F_V3F 
#endif
 
#ifdef GL_T4F_C4F_N3F_V4F 
#endif
 
#ifdef GL_SMOOTH_POINT_SIZE_RANGE 
#endif
 
#ifdef GL_SMOOTH_POINT_SIZE_GRANULARITY 
#endif
 
#ifdef GL_SMOOTH_LINE_WIDTH_RANGE 
#endif
 
#ifdef GL_SMOOTH_LINE_WIDTH_GRANULARITY 
#endif
 
#ifdef GL_CLIP_DISTANCE0 
#endif
 
#ifdef GL_CLIP_DISTANCE1 
#endif
 
#ifdef GL_CLIP_DISTANCE2 
#endif
 
#ifdef GL_CLIP_DISTANCE3 
#endif
 
#ifdef GL_CLIP_DISTANCE4 
#endif
 
#ifdef GL_CLIP_DISTANCE5 
#endif
 
#ifdef GL_CLIP_DISTANCE6 
#endif
 
#ifdef GL_CLIP_DISTANCE7 
#endif
 
#ifdef GL_MAX_CLIP_DISTANCES 
#endif
 
#ifdef GL_INVALID_FRAMEBUFFER_OPERATION 
#endif
 
#ifdef GL_HALF_FLOAT 
#endif
 
#ifdef GL_LINES_ADJACENCY 
#endif
 
#ifdef GL_LINE_STRIP_ADJACENCY 
#endif
 
#ifdef GL_TRIANGLES_ADJACENCY 
#endif
 
#ifdef GL_TRIANGLE_STRIP_ADJACENCY 
#endif
 
#ifdef GL_PATCHES 
#endif
 
#ifdef GL_FIXED 
#endif

/**
 * @vendor ARB
 * The primary GL enumerant space begins here. All modern enum allocations are
 * in this range. These enums are mostly assigned the default class since it's a
 * great deal of not very useful work to be more specific 
 */
 
#ifdef GL_POLYGON_OFFSET_FILL 
#endif
 
#ifdef GL_POLYGON_OFFSET_FACTOR 
#endif
 
#ifdef GL_TEXTURE_BINDING_1D 
#endif
 
#ifdef GL_TEXTURE_BINDING_2D 
#endif
 
#ifdef GL_TEXTURE_RED_SIZE 
#endif
 
#ifdef GL_TEXTURE_GREEN_SIZE 
#endif
 
#ifdef GL_TEXTURE_BLUE_SIZE 
#endif
 
#ifdef GL_TEXTURE_ALPHA_SIZE 
#endif
 
#ifdef GL_PROXY_TEXTURE_1D 
#endif
 
#ifdef GL_PROXY_TEXTURE_2D 
#endif
 
#ifdef GL_RGB4 
#endif
 
#ifdef GL_RGB5 
#endif
 
#ifdef GL_RGB8 
#endif
 
#ifdef GL_RGB10 
#endif
 
#ifdef GL_RGB12 
#endif
 
#ifdef GL_RGB16 
#endif
 
#ifdef GL_RGBA2 
#endif
 
#ifdef GL_RGBA4 
#endif
 
#ifdef GL_RGB5_A1 
#endif
 
#ifdef GL_RGBA8 
#endif
 
#ifdef GL_RGB10_A2 
#endif
 
#ifdef GL_RGBA12 
#endif
 
#ifdef GL_RGBA16 
#endif
 
#ifdef GL_VERTEX_ARRAY_POINTER 
#endif
 
#ifdef GL_NORMAL_ARRAY_POINTER 
#endif
 
#ifdef GL_COLOR_ARRAY_POINTER 
#endif
 
#ifdef GL_INDEX_ARRAY_POINTER 
#endif
 
#ifdef GL_TEXTURE_COORD_ARRAY_POINTER 
#endif
 
#ifdef GL_EDGE_FLAG_ARRAY_POINTER 
#endif
 
#ifdef GL_VERTEX_ARRAY 
#endif
 
#ifdef GL_NORMAL_ARRAY 
#endif
 
#ifdef GL_COLOR_ARRAY 
#endif
 
#ifdef GL_INDEX_ARRAY 
#endif
 
#ifdef GL_TEXTURE_COORD_ARRAY 
#endif
 
#ifdef GL_EDGE_FLAG_ARRAY 
#endif
 
#ifdef GL_VERTEX_ARRAY_SIZE 
#endif
 
#ifdef GL_VERTEX_ARRAY_TYPE 
#endif
 
#ifdef GL_VERTEX_ARRAY_STRIDE 
#endif
 
#ifdef GL_NORMAL_ARRAY_TYPE 
#endif
 
#ifdef GL_NORMAL_ARRAY_STRIDE 
#endif
 
#ifdef GL_COLOR_ARRAY_SIZE 
#endif
 
#ifdef GL_COLOR_ARRAY_TYPE 
#endif
 
#ifdef GL_COLOR_ARRAY_STRIDE 
#endif
 
#ifdef GL_INDEX_ARRAY_TYPE 
#endif
 
#ifdef GL_INDEX_ARRAY_STRIDE 
#endif
 
#ifdef GL_TEXTURE_COORD_ARRAY_SIZE 
#endif
 
#ifdef GL_TEXTURE_COORD_ARRAY_TYPE 
#endif
 
#ifdef GL_TEXTURE_COORD_ARRAY_STRIDE 
#endif
 
#ifdef GL_EDGE_FLAG_ARRAY_STRIDE 
#endif
 
#ifdef GL_TEXTURE_LUMINANCE_SIZE 
#endif
 
#ifdef GL_TEXTURE_INTENSITY_SIZE 
#endif
 
#ifdef GL_TEXTURE_PRIORITY 
#endif
 
#ifdef GL_TEXTURE_RESIDENT 
#endif
 
#ifdef GL_ALPHA4 
#endif
 
#ifdef GL_ALPHA8 
#endif
 
#ifdef GL_ALPHA12 
#endif
 
#ifdef GL_ALPHA16 
#endif
 
#ifdef GL_LUMINANCE4 
#endif
 
#ifdef GL_LUMINANCE8 
#endif
 
#ifdef GL_LUMINANCE12 
#endif
 
#ifdef GL_LUMINANCE16 
#endif
 
#ifdef GL_LUMINANCE4_ALPHA4 
#endif
 
#ifdef GL_LUMINANCE6_ALPHA2 
#endif
 
#ifdef GL_LUMINANCE8_ALPHA8 
#endif
 
#ifdef GL_LUMINANCE12_ALPHA4 
#endif
 
#ifdef GL_LUMINANCE12_ALPHA12 
#endif
 
#ifdef GL_LUMINANCE16_ALPHA16 
#endif
 
#ifdef GL_INTENSITY 
#endif
 
#ifdef GL_INTENSITY4 
#endif
 
#ifdef GL_INTENSITY8 
#endif
 
#ifdef GL_INTENSITY12 
#endif
 
#ifdef GL_INTENSITY16 
#endif
 
#ifdef GL_UNSIGNED_BYTE_3_3_2 
#endif
 
#ifdef GL_UNSIGNED_SHORT_4_4_4_4 
#endif
 
#ifdef GL_UNSIGNED_SHORT_5_5_5_1 
#endif
 
#ifdef GL_UNSIGNED_INT_8_8_8_8 
#endif
 
#ifdef GL_UNSIGNED_INT_10_10_10_2 
#endif
 
#ifdef GL_TEXTURE_BINDING_3D 
#endif
 
#ifdef GL_PACK_SKIP_IMAGES 
#endif
 
#ifdef GL_PACK_IMAGE_HEIGHT 
#endif
 
#ifdef GL_UNPACK_SKIP_IMAGES 
#endif
 
#ifdef GL_UNPACK_IMAGE_HEIGHT 
#endif
 
#ifdef GL_TEXTURE_3D 
#endif
 
#ifdef GL_PROXY_TEXTURE_3D 
#endif
 
#ifdef GL_TEXTURE_DEPTH 
#endif
 
#ifdef GL_TEXTURE_WRAP_R 
#endif
 
#ifdef GL_MAX_3D_TEXTURE_SIZE 
#endif
 
#ifdef GL_RESCALE_NORMAL 
#endif
 
#ifdef GL_MULTISAMPLE 
#endif
 
#ifdef GL_SAMPLE_ALPHA_TO_COVERAGE 
#endif
 
#ifdef GL_SAMPLE_ALPHA_TO_ONE 
#endif
 
#ifdef GL_SAMPLE_COVERAGE 
#endif
 
#ifdef GL_SAMPLE_BUFFERS 
#endif
 
#ifdef GL_SAMPLES 
#endif
 
#ifdef GL_SAMPLE_COVERAGE_VALUE 
#endif
 
#ifdef GL_SAMPLE_COVERAGE_INVERT 
#endif
 
#ifdef GL_BLEND_COLOR 
#endif
 
#ifdef GL_BLEND_EQUATION 
#endif
 
#ifdef GL_CONSTANT_COLOR 
#endif
 
#ifdef GL_ONE_MINUS_CONSTANT_COLOR 
#endif
 
#ifdef GL_CONSTANT_ALPHA 
#endif
 
#ifdef GL_ONE_MINUS_CONSTANT_ALPHA 
#endif
 
#ifdef GL_FUNC_ADD 
#endif
 
#ifdef GL_FUNC_REVERSE_SUBTRACT 
#endif
 
#ifdef GL_FUNC_SUBTRACT 
#endif
 
#ifdef GL_MIN 
#endif
 
#ifdef GL_MAX 
#endif
 
#ifdef GL_BLEND_EQUATION_RGB 
#endif

/**
 
 * ClientAttribMask 
 * ----------------------------------------------------------------------------
 *  
 */
 
#ifdef GL_CLIENT_PIXEL_STORE_BIT 
#endif
 
#ifdef GL_CLIENT_VERTEX_ARRAY_BIT 
#endif
 
#ifdef GL_CLIENT_ALL_ATTRIB_BITS 
#endif

/**
 * @vendor SGI 
 */
 
#ifdef GL_UNSIGNED_BYTE_2_3_3_REV 
#endif
 
#ifdef GL_UNSIGNED_SHORT_5_6_5 
#endif
 
#ifdef GL_UNSIGNED_SHORT_5_6_5_REV 
#endif
 
#ifdef GL_UNSIGNED_SHORT_4_4_4_4_REV 
#endif
 
#ifdef GL_UNSIGNED_SHORT_1_5_5_5_REV 
#endif
 
#ifdef GL_UNSIGNED_INT_8_8_8_8_REV 
#endif
 
#ifdef GL_UNSIGNED_INT_2_10_10_10_REV 
#endif

/**
 * @vendor MS 
 */
 
#ifdef GL_BGR 
#endif
 
#ifdef GL_BGRA 
#endif
 
#ifdef GL_MAX_ELEMENTS_VERTICES 
#endif
 
#ifdef GL_MAX_ELEMENTS_INDICES 
#endif

/**
 * @vendor SGI 
 */
 
#ifdef GL_CLAMP_TO_EDGE 
#endif
 
#ifdef GL_TEXTURE_MIN_LOD 
#endif
 
#ifdef GL_TEXTURE_MAX_LOD 
#endif
 
#ifdef GL_TEXTURE_BASE_LEVEL 
#endif
 
#ifdef GL_TEXTURE_MAX_LEVEL 
#endif
 
#ifdef GL_CLAMP_TO_BORDER 
#endif
 
#ifdef GL_POINT_FADE_THRESHOLD_SIZE 
#endif
 
#ifdef GL_POINT_SIZE_MIN 
#endif
 
#ifdef GL_POINT_SIZE_MAX 
#endif
 
#ifdef GL_POINT_DISTANCE_ATTENUATION 
#endif

/**
 * @vendor SGI 
 */
 
#ifdef GL_ALIASED_LINE_WIDTH_RANGE 
#endif
 
#ifdef GL_ALIASED_POINT_SIZE_RANGE 
#endif
 
#ifdef GL_FOG_COORDINATE_SOURCE 
#endif
 
#ifdef GL_FOG_COORDINATE 
#endif
 
#ifdef GL_FRAGMENT_DEPTH 
#endif
 
#ifdef GL_CURRENT_FOG_COORDINATE 
#endif
 
#ifdef GL_FOG_COORDINATE_ARRAY_TYPE 
#endif
 
#ifdef GL_FOG_COORDINATE_ARRAY_STRIDE 
#endif
 
#ifdef GL_FOG_COORDINATE_ARRAY_POINTER 
#endif
 
#ifdef GL_FOG_COORDINATE_ARRAY 
#endif
 
#ifdef GL_COLOR_SUM 
#endif
 
#ifdef GL_CURRENT_SECONDARY_COLOR 
#endif
 
#ifdef GL_SECONDARY_COLOR_ARRAY_SIZE 
#endif
 
#ifdef GL_SECONDARY_COLOR_ARRAY_TYPE 
#endif
 
#ifdef GL_SECONDARY_COLOR_ARRAY_STRIDE 
#endif
 
#ifdef GL_SECONDARY_COLOR_ARRAY_POINTER 
#endif
 
#ifdef GL_SECONDARY_COLOR_ARRAY 
#endif
 
#ifdef GL_FOG_COORD_SRC 
#endif
 
#ifdef GL_FOG_COORD 
#endif
 
#ifdef GL_CURRENT_FOG_COORD 
#endif
 
#ifdef GL_FOG_COORD_ARRAY_TYPE 
#endif
 
#ifdef GL_FOG_COORD_ARRAY_STRIDE 
#endif
 
#ifdef GL_FOG_COORD_ARRAY_POINTER 
#endif
 
#ifdef GL_FOG_COORD_ARRAY 
#endif
 
#ifdef GL_CURRENT_RASTER_SECONDARY_COLOR 
#endif

/**
 * @vendor SGI 
 */
 
#ifdef GL_LIGHT_MODEL_COLOR_CONTROL 
#endif
 
#ifdef GL_SINGLE_COLOR 
#endif
 
#ifdef GL_SEPARATE_SPECULAR_COLOR 
#endif

/**
 * @vendor ARB 
 */
 
#ifdef GL_TEXTURE0 
#endif
 
#ifdef GL_TEXTURE1 
#endif
 
#ifdef GL_TEXTURE2 
#endif
 
#ifdef GL_TEXTURE3 
#endif
 
#ifdef GL_TEXTURE4 
#endif
 
#ifdef GL_TEXTURE5 
#endif
 
#ifdef GL_TEXTURE6 
#endif
 
#ifdef GL_TEXTURE7 
#endif
 
#ifdef GL_TEXTURE8 
#endif
 
#ifdef GL_TEXTURE9 
#endif
 
#ifdef GL_TEXTURE10 
#endif
 
#ifdef GL_TEXTURE11 
#endif
 
#ifdef GL_TEXTURE12 
#endif
 
#ifdef GL_TEXTURE13 
#endif
 
#ifdef GL_TEXTURE14 
#endif
 
#ifdef GL_TEXTURE15 
#endif
 
#ifdef GL_TEXTURE16 
#endif
 
#ifdef GL_TEXTURE17 
#endif
 
#ifdef GL_TEXTURE18 
#endif
 
#ifdef GL_TEXTURE19 
#endif
 
#ifdef GL_TEXTURE20 
#endif
 
#ifdef GL_TEXTURE21 
#endif
 
#ifdef GL_TEXTURE22 
#endif
 
#ifdef GL_TEXTURE23 
#endif
 
#ifdef GL_TEXTURE24 
#endif
 
#ifdef GL_TEXTURE25 
#endif
 
#ifdef GL_TEXTURE26 
#endif
 
#ifdef GL_TEXTURE27 
#endif
 
#ifdef GL_TEXTURE28 
#endif
 
#ifdef GL_TEXTURE29 
#endif
 
#ifdef GL_TEXTURE30 
#endif
 
#ifdef GL_TEXTURE31 
#endif
 
#ifdef GL_ACTIVE_TEXTURE 
#endif
 
#ifdef GL_COMPRESSED_RGB 
#endif
 
#ifdef GL_COMPRESSED_RGBA 
#endif
 
#ifdef GL_TEXTURE_COMPRESSION_HINT 
#endif
 
#ifdef GL_CLIENT_ACTIVE_TEXTURE 
#endif
 
#ifdef GL_MAX_TEXTURE_UNITS 
#endif
 
#ifdef GL_TRANSPOSE_MODELVIEW_MATRIX 
#endif
 
#ifdef GL_TRANSPOSE_PROJECTION_MATRIX 
#endif
 
#ifdef GL_TRANSPOSE_TEXTURE_MATRIX 
#endif
 
#ifdef GL_TRANSPOSE_COLOR_MATRIX 
#endif
 
#ifdef GL_COMPRESSED_ALPHA 
#endif
 
#ifdef GL_COMPRESSED_LUMINANCE 
#endif
 
#ifdef GL_COMPRESSED_LUMINANCE_ALPHA 
#endif
 
#ifdef GL_COMPRESSED_INTENSITY 
#endif
 
#ifdef GL_SUBTRACT 
#endif
 
#ifdef GL_MAX_RENDERBUFFER_SIZE 
#endif

/**
 * @vendor NV 
 */
 
#ifdef GL_TEXTURE_CUBE_MAP 
#endif
 
#ifdef GL_TEXTURE_BINDING_CUBE_MAP 
#endif
 
#ifdef GL_TEXTURE_CUBE_MAP_POSITIVE_X 
#endif
 
#ifdef GL_TEXTURE_CUBE_MAP_NEGATIVE_X 
#endif
 
#ifdef GL_TEXTURE_CUBE_MAP_POSITIVE_Y 
#endif
 
#ifdef GL_TEXTURE_CUBE_MAP_NEGATIVE_Y 
#endif
 
#ifdef GL_TEXTURE_CUBE_MAP_POSITIVE_Z 
#endif
 
#ifdef GL_TEXTURE_CUBE_MAP_NEGATIVE_Z 
#endif
 
#ifdef GL_PROXY_TEXTURE_CUBE_MAP 
#endif
 
#ifdef GL_MAX_CUBE_MAP_TEXTURE_SIZE 
#endif
 
#ifdef GL_NORMAL_MAP 
#endif
 
#ifdef GL_REFLECTION_MAP 
#endif
 
#ifdef GL_MAX_TEXTURE_LOD_BIAS 
#endif
 
#ifdef GL_TEXTURE_LOD_BIAS 
#endif
 
#ifdef GL_INCR_WRAP 
#endif
 
#ifdef GL_DECR_WRAP 
#endif
 
#ifdef GL_TEXTURE_FILTER_CONTROL 
#endif
 
#ifdef GL_DEPTH_STENCIL 
#endif
 
#ifdef GL_UNSIGNED_INT_24_8 
#endif
 
#ifdef GL_TEXTURE_RECTANGLE 
#endif
 
#ifdef GL_TEXTURE_BINDING_RECTANGLE 
#endif
 
#ifdef GL_PROXY_TEXTURE_RECTANGLE 
#endif
 
#ifdef GL_MAX_RECTANGLE_TEXTURE_SIZE 
#endif
 
#ifdef GL_UNIFORM_BLOCK_REFERENCED_BY_TESS_CONTROL_SHADER 
#endif
 
#ifdef GL_UNIFORM_BLOCK_REFERENCED_BY_TESS_EVALUATION_SHADER 
#endif

/**
 * @vendor ARB 
 */
 
#ifdef GL_TEXTURE_COMPRESSED_IMAGE_SIZE 
#endif
 
#ifdef GL_TEXTURE_COMPRESSED 
#endif
 
#ifdef GL_NUM_COMPRESSED_TEXTURE_FORMATS 
#endif
 
#ifdef GL_COMPRESSED_TEXTURE_FORMATS 
#endif
 
#ifdef GL_DOT3_RGB 
#endif
 
#ifdef GL_DOT3_RGBA 
#endif

/**
 
 * RegisterCombinerPname 
 * ----------------------------------------------------------------------------
 * @vendor AMD/NV 
 */
 
#ifdef GL_COMBINE 
#endif
 
#ifdef GL_COMBINE_RGB 
#endif
 
#ifdef GL_COMBINE_ALPHA 
#endif
 
#ifdef GL_SOURCE0_RGB 
#endif
 
#ifdef GL_SOURCE1_RGB 
#endif
 
#ifdef GL_SOURCE2_RGB 
#endif
 
#ifdef GL_SOURCE0_ALPHA 
#endif
 
#ifdef GL_SOURCE1_ALPHA 
#endif
 
#ifdef GL_SOURCE2_ALPHA 
#endif
 
#ifdef GL_OPERAND0_RGB 
#endif
 
#ifdef GL_OPERAND1_RGB 
#endif
 
#ifdef GL_OPERAND2_RGB 
#endif
 
#ifdef GL_OPERAND0_ALPHA 
#endif
 
#ifdef GL_OPERAND1_ALPHA 
#endif
 
#ifdef GL_OPERAND2_ALPHA 
#endif
 
#ifdef GL_RGB_SCALE 
#endif
 
#ifdef GL_ADD_SIGNED 
#endif
 
#ifdef GL_INTERPOLATE 
#endif
 
#ifdef GL_CONSTANT 
#endif
 
#ifdef GL_PRIMARY_COLOR 
#endif
 
#ifdef GL_PREVIOUS 
#endif
 
#ifdef GL_SRC1_ALPHA 
#endif
 
#ifdef GL_SRC0_RGB 
#endif
 
#ifdef GL_SRC1_RGB 
#endif
 
#ifdef GL_SRC2_RGB 
#endif
 
#ifdef GL_SRC0_ALPHA 
#endif
 
#ifdef GL_SRC2_ALPHA 
#endif

/**
 * @vendor ZiiLabs 
 */
 
#ifdef GL_BLEND_DST_RGB 
#endif
 
#ifdef GL_BLEND_SRC_RGB 
#endif
 
#ifdef GL_BLEND_DST_ALPHA 
#endif
 
#ifdef GL_BLEND_SRC_ALPHA 
#endif

/**
 * @vendor SGI 
 */
 
#ifdef GL_DEPTH_COMPONENT16 
#endif
 
#ifdef GL_DEPTH_COMPONENT24 
#endif
 
#ifdef GL_DEPTH_COMPONENT32 
#endif
 
#ifdef GL_GENERATE_MIPMAP 
#endif
 
#ifdef GL_GENERATE_MIPMAP_HINT 
#endif

/**
 * @vendor HP 
 */
 
#ifdef GL_MIRRORED_REPEAT 
#endif

/**
 * @vendor ARB 
 */
 
#ifdef GL_TEXTURE_DEPTH_SIZE 
#endif
 
#ifdef GL_TEXTURE_COMPARE_MODE 
#endif
 
#ifdef GL_TEXTURE_COMPARE_FUNC 
#endif
 
#ifdef GL_DEPTH_TEXTURE_MODE 
#endif
 
#ifdef GL_COMPARE_R_TO_TEXTURE 
#endif
 
#ifdef GL_COMPARE_REF_TO_TEXTURE 
#endif
 
#ifdef GL_TEXTURE_CUBE_MAP_SEAMLESS 
#endif

/**
 * @vendor AMD 
 */
 
#ifdef GL_BUFFER_SIZE 
#endif
 
#ifdef GL_BUFFER_USAGE 
#endif
 
#ifdef GL_STENCIL_BACK_FUNC 
#endif
 
#ifdef GL_STENCIL_BACK_FAIL 
#endif
 
#ifdef GL_STENCIL_BACK_PASS_DEPTH_FAIL 
#endif
 
#ifdef GL_STENCIL_BACK_PASS_DEPTH_PASS 
#endif
 
#ifdef GL_MAX_DRAW_BUFFERS 
#endif
 
#ifdef GL_DRAW_BUFFER0 
#endif
 
#ifdef GL_DRAW_BUFFER1 
#endif
 
#ifdef GL_DRAW_BUFFER2 
#endif
 
#ifdef GL_DRAW_BUFFER3 
#endif
 
#ifdef GL_DRAW_BUFFER4 
#endif
 
#ifdef GL_DRAW_BUFFER5 
#endif
 
#ifdef GL_DRAW_BUFFER6 
#endif
 
#ifdef GL_DRAW_BUFFER7 
#endif
 
#ifdef GL_DRAW_BUFFER8 
#endif
 
#ifdef GL_DRAW_BUFFER9 
#endif
 
#ifdef GL_DRAW_BUFFER10 
#endif
 
#ifdef GL_DRAW_BUFFER11 
#endif
 
#ifdef GL_DRAW_BUFFER12 
#endif
 
#ifdef GL_DRAW_BUFFER13 
#endif
 
#ifdef GL_DRAW_BUFFER14 
#endif
 
#ifdef GL_DRAW_BUFFER15 
#endif
 
#ifdef GL_BLEND_EQUATION_ALPHA 
#endif
 
#ifdef GL_RGBA32F 
#endif
 
#ifdef GL_RGB32F 
#endif
 
#ifdef GL_RGBA16F 
#endif
 
#ifdef GL_RGB16F 
#endif
 
#ifdef GL_NUM_PROGRAM_BINARY_FORMATS 
#endif
 
#ifdef GL_PROGRAM_BINARY_FORMATS 
#endif

/**
 * @vendor NV 
 */
 
#ifdef GL_QUERY_COUNTER_BITS 
#endif
 
#ifdef GL_CURRENT_QUERY 
#endif
 
#ifdef GL_QUERY_RESULT 
#endif
 
#ifdef GL_QUERY_RESULT_AVAILABLE 
#endif
 
#ifdef GL_ARRAY_BUFFER 
#endif
 
#ifdef GL_ELEMENT_ARRAY_BUFFER 
#endif
 
#ifdef GL_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_ELEMENT_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_VERTEX_ATTRIB_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_READ_ONLY 
#endif
 
#ifdef GL_WRITE_ONLY 
#endif
 
#ifdef GL_READ_WRITE 
#endif
 
#ifdef GL_BUFFER_ACCESS 
#endif
 
#ifdef GL_BUFFER_MAPPED 
#endif
 
#ifdef GL_BUFFER_MAP_POINTER 
#endif
 
#ifdef GL_STREAM_DRAW 
#endif
 
#ifdef GL_STREAM_READ 
#endif
 
#ifdef GL_STREAM_COPY 
#endif
 
#ifdef GL_STATIC_DRAW 
#endif
 
#ifdef GL_STATIC_READ 
#endif
 
#ifdef GL_STATIC_COPY 
#endif
 
#ifdef GL_DYNAMIC_DRAW 
#endif
 
#ifdef GL_DYNAMIC_READ 
#endif
 
#ifdef GL_DYNAMIC_COPY 
#endif
 
#ifdef GL_SAMPLES_PASSED 
#endif
 
#ifdef GL_VERTEX_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_NORMAL_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_COLOR_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_INDEX_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_TEXTURE_COORD_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_EDGE_FLAG_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_SECONDARY_COLOR_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_FOG_COORDINATE_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_WEIGHT_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_FOG_COORD_ARRAY_BUFFER_BINDING 
#endif
 
#ifdef GL_MAX_VERTEX_ATTRIBS 
#endif
 
#ifdef GL_VERTEX_ATTRIB_ARRAY_NORMALIZED 
#endif
 
#ifdef GL_MAX_TEXTURE_IMAGE_UNITS 
#endif
 
#ifdef GL_POINT_SPRITE 
#endif
 
#ifdef GL_COORD_REPLACE 
#endif
 
#ifdef GL_MAX_TEXTURE_COORDS 
#endif
 
#ifdef GL_PIXEL_PACK_BUFFER 
#endif
 
#ifdef GL_PIXEL_UNPACK_BUFFER 
#endif
 
#ifdef GL_PIXEL_PACK_BUFFER_BINDING 
#endif
 
#ifdef GL_PIXEL_UNPACK_BUFFER_BINDING 
#endif
 
#ifdef GL_VERTEX_ATTRIB_ARRAY_INTEGER 
#endif
 
#ifdef GL_MAX_ARRAY_TEXTURE_LAYERS 
#endif
 
#ifdef GL_MIN_PROGRAM_TEXEL_OFFSET 
#endif
 
#ifdef GL_MAX_PROGRAM_TEXEL_OFFSET 
#endif
 
#ifdef GL_CLAMP_READ_COLOR 
#endif
 
#ifdef GL_FIXED_ONLY 
#endif
 
#ifdef GL_DEPTH24_STENCIL8 
#endif
 
#ifdef GL_TEXTURE_STENCIL_SIZE 
#endif
 
#ifdef GL_CLAMP_VERTEX_COLOR 
#endif
 
#ifdef GL_CLAMP_FRAGMENT_COLOR 
#endif
 
#ifdef GL_GEOMETRY_VERTICES_OUT 
#endif
 
#ifdef GL_GEOMETRY_INPUT_TYPE 
#endif
 
#ifdef GL_GEOMETRY_OUTPUT_TYPE 
#endif
 
#ifdef GL_VERTEX_ATTRIB_ARRAY_DIVISOR 
#endif
 
#ifdef GL_SRC1_COLOR 
#endif
 
#ifdef GL_ONE_MINUS_SRC1_COLOR 
#endif
 
#ifdef GL_ONE_MINUS_SRC1_ALPHA 
#endif
 
#ifdef GL_MAX_DUAL_SOURCE_DRAW_BUFFERS 
#endif
 
#ifdef GL_SAMPLER_BINDING 
#endif
 
#ifdef GL_TIME_ELAPSED 
#endif
 
#ifdef GL_GEOMETRY_SHADER_INVOCATIONS 
#endif
 
#ifdef GL_MAX_TESS_CONTROL_INPUT_COMPONENTS 
#endif
 
#ifdef GL_MAX_TESS_EVALUATION_INPUT_COMPONENTS 
#endif

/**
 * @vendor NV 
 */
 
#ifdef GL_VERTEX_ATTRIB_ARRAY_ENABLED 
#endif
 
#ifdef GL_VERTEX_ATTRIB_ARRAY_SIZE 
#endif
 
#ifdef GL_VERTEX_ATTRIB_ARRAY_STRIDE 
#endif
 
#ifdef GL_VERTEX_ATTRIB_ARRAY_TYPE 
#endif
 
#ifdef GL_CURRENT_VERTEX_ATTRIB 
#endif
 
#ifdef GL_VERTEX_PROGRAM_POINT_SIZE 
#endif
 
#ifdef GL_VERTEX_ATTRIB_ARRAY_POINTER 
#endif
 
#ifdef GL_VERTEX_PROGRAM_TWO_SIDE 
#endif
 
#ifdef GL_PROGRAM_POINT_SIZE 
#endif
 
#ifdef GL_DEPTH_CLAMP 
#endif

/**
 
 * ShaderType 
 * ----------------------------------------------------------------------------
 * @vendor ARB 
 */
 
#ifdef GL_FRAGMENT_SHADER 
#endif
 
#ifdef GL_VERTEX_SHADER 
#endif

/**
 * @vendor ARB 
 */
 
#ifdef GL_MAX_FRAGMENT_UNIFORM_COMPONENTS 
#endif
 
#ifdef GL_MAX_VERTEX_UNIFORM_COMPONENTS 
#endif
 
#ifdef GL_MAX_VARYING_FLOATS 
#endif
 
#ifdef GL_MAX_VERTEX_TEXTURE_IMAGE_UNITS 
#endif
 
#ifdef GL_MAX_COMBINED_TEXTURE_IMAGE_UNITS 
#endif
 
#ifdef GL_SHADER_TYPE 
#endif
 
#ifdef GL_MAX_VARYING_COMPONENTS 
#endif

/**
 
 * AttributeType 
 * ----------------------------------------------------------------------------
 * @vendor ARB 
 */
 
#ifdef GL_FLOAT_VEC2 
#endif
 
#ifdef GL_FLOAT_VEC3 
#endif
 
#ifdef GL_FLOAT_VEC4 
#endif
 
#ifdef GL_INT_VEC2 
#endif
 
#ifdef GL_INT_VEC3 
#endif
 
#ifdef GL_INT_VEC4 
#endif
 
#ifdef GL_BOOL 
#endif
 
#ifdef GL_BOOL_VEC2 
#endif
 
#ifdef GL_BOOL_VEC3 
#endif
 
#ifdef GL_BOOL_VEC4 
#endif
 
#ifdef GL_FLOAT_MAT2 
#endif
 
#ifdef GL_FLOAT_MAT3 
#endif
 
#ifdef GL_FLOAT_MAT4 
#endif
 
#ifdef GL_SAMPLER_1D 
#endif
 
#ifdef GL_SAMPLER_2D 
#endif
 
#ifdef GL_SAMPLER_3D 
#endif
 
#ifdef GL_SAMPLER_CUBE 
#endif
 
#ifdef GL_SAMPLER_1D_SHADOW 
#endif
 
#ifdef GL_SAMPLER_2D_SHADOW 
#endif
 
#ifdef GL_FLOAT_MAT2x3 
#endif
 
#ifdef GL_FLOAT_MAT2x4 
#endif
 
#ifdef GL_FLOAT_MAT3x2 
#endif
 
#ifdef GL_FLOAT_MAT3x4 
#endif
 
#ifdef GL_FLOAT_MAT4x2 
#endif
 
#ifdef GL_FLOAT_MAT4x3 
#endif
 
#ifdef GL_SAMPLER_2D_RECT 
#endif
 
#ifdef GL_SAMPLER_2D_RECT_SHADOW 
#endif

/**
 * @vendor ARB 
 */
 
#ifdef GL_DELETE_STATUS 
#endif
 
#ifdef GL_COMPILE_STATUS 
#endif
 
#ifdef GL_LINK_STATUS 
#endif
 
#ifdef GL_VALIDATE_STATUS 
#endif
 
#ifdef GL_INFO_LOG_LENGTH 
#endif
 
#ifdef GL_ATTACHED_SHADERS 
#endif
 
#ifdef GL_ACTIVE_UNIFORMS 
#endif
 
#ifdef GL_ACTIVE_UNIFORM_MAX_LENGTH 
#endif
 
#ifdef GL_SHADER_SOURCE_LENGTH 
#endif
 
#ifdef GL_ACTIVE_ATTRIBUTES 
#endif
 
#ifdef GL_ACTIVE_ATTRIBUTE_MAX_LENGTH 
#endif
 
#ifdef GL_FRAGMENT_SHADER_DERIVATIVE_HINT 
#endif
 
#ifdef GL_SHADING_LANGUAGE_VERSION 
#endif
 
#ifdef GL_CURRENT_PROGRAM 
#endif

/**
 * @vendor ARB 
 */
 
#ifdef GL_POINT_SPRITE_COORD_ORIGIN 
#endif
 
#ifdef GL_LOWER_LEFT 
#endif
 
#ifdef GL_UPPER_LEFT 
#endif
 
#ifdef GL_STENCIL_BACK_REF 
#endif
 
#ifdef GL_STENCIL_BACK_VALUE_MASK 
#endif
 
#ifdef GL_STENCIL_BACK_WRITEMASK 
#endif
 
#ifdef GL_DEPTH_COMPONENT32F 
#endif
 
#ifdef GL_DEPTH32F_STENCIL8 
#endif
 
#ifdef GL_FRAMEBUFFER_BINDING 
#endif
 
#ifdef GL_DRAW_FRAMEBUFFER_BINDING 
#endif
 
#ifdef GL_RENDERBUFFER_BINDING 
#endif
 
#ifdef GL_READ_FRAMEBUFFER 
#endif
 
#ifdef GL_DRAW_FRAMEBUFFER 
#endif
 
#ifdef GL_READ_FRAMEBUFFER_BINDING 
#endif
 
#ifdef GL_RENDERBUFFER_SAMPLES 
#endif

/**
 * @vendor NV
 * For Pat Brown 
 */
 
#ifdef GL_SRGB 
#endif
 
#ifdef GL_SRGB8 
#endif
 
#ifdef GL_SRGB_ALPHA 
#endif
 
#ifdef GL_SRGB8_ALPHA8 
#endif
 
#ifdef GL_COMPRESSED_SRGB 
#endif
 
#ifdef GL_COMPRESSED_SRGB_ALPHA 
#endif
 
#ifdef GL_SLUMINANCE_ALPHA 
#endif
 
#ifdef GL_SLUMINANCE8_ALPHA8 
#endif
 
#ifdef GL_SLUMINANCE 
#endif
 
#ifdef GL_SLUMINANCE8 
#endif
 
#ifdef GL_COMPRESSED_SLUMINANCE 
#endif
 
#ifdef GL_COMPRESSED_SLUMINANCE_ALPHA 
#endif
 
#ifdef GL_TEXTURE_1D_ARRAY 
#endif
 
#ifdef GL_PROXY_TEXTURE_1D_ARRAY 
#endif
 
#ifdef GL_TEXTURE_2D_ARRAY 
#endif
 
#ifdef GL_PROXY_TEXTURE_2D_ARRAY 
#endif
 
#ifdef GL_TEXTURE_BINDING_1D_ARRAY 
#endif
 
#ifdef GL_TEXTURE_BINDING_2D_ARRAY 
#endif
 
#ifdef GL_R11F_G11F_B10F 
#endif
 
#ifdef GL_UNSIGNED_INT_10F_11F_11F_REV 
#endif
 
#ifdef GL_RGB9_E5 
#endif
 
#ifdef GL_UNSIGNED_INT_5_9_9_9_REV 
#endif
 
#ifdef GL_TEXTURE_SHARED_SIZE 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_VARYING_MAX_LENGTH 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_BUFFER_MODE 
#endif
 
#ifdef GL_MAX_TRANSFORM_FEEDBACK_SEPARATE_COMPONENTS 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_VARYINGS 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_BUFFER_START 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_BUFFER_SIZE 
#endif
 
#ifdef GL_PRIMITIVES_GENERATED 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_PRIMITIVES_WRITTEN 
#endif
 
#ifdef GL_RASTERIZER_DISCARD 
#endif
 
#ifdef GL_MAX_TRANSFORM_FEEDBACK_INTERLEAVED_COMPONENTS 
#endif
 
#ifdef GL_MAX_TRANSFORM_FEEDBACK_SEPARATE_ATTRIBS 
#endif
 
#ifdef GL_INTERLEAVED_ATTRIBS 
#endif
 
#ifdef GL_SEPARATE_ATTRIBS 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_BUFFER 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_BUFFER_BINDING 
#endif
 
#ifdef GL_TEXTURE_RED_TYPE 
#endif
 
#ifdef GL_TEXTURE_GREEN_TYPE 
#endif
 
#ifdef GL_TEXTURE_BLUE_TYPE 
#endif
 
#ifdef GL_TEXTURE_ALPHA_TYPE 
#endif
 
#ifdef GL_TEXTURE_DEPTH_TYPE 
#endif
 
#ifdef GL_UNSIGNED_NORMALIZED 
#endif
 
#ifdef GL_TEXTURE_LUMINANCE_TYPE 
#endif
 
#ifdef GL_TEXTURE_INTENSITY_TYPE 
#endif
 
#ifdef GL_TEXTURE_BUFFER 
#endif
 
#ifdef GL_MAX_TEXTURE_BUFFER_SIZE 
#endif
 
#ifdef GL_TEXTURE_BINDING_BUFFER 
#endif
 
#ifdef GL_TEXTURE_BUFFER_DATA_STORE_BINDING 
#endif
 
#ifdef GL_MAX_GEOMETRY_TEXTURE_IMAGE_UNITS 
#endif
 
#ifdef GL_ANY_SAMPLES_PASSED 
#endif
 
#ifdef GL_SAMPLE_SHADING 
#endif
 
#ifdef GL_MIN_SAMPLE_SHADING_VALUE 
#endif

/**
 * @vendor ARB 
 */
 
#ifdef GL_MAJOR_VERSION 
#endif
 
#ifdef GL_MINOR_VERSION 
#endif
 
#ifdef GL_NUM_EXTENSIONS 
#endif
 
#ifdef GL_CONTEXT_FLAGS 
#endif
 
#ifdef GL_COMPRESSED_RED 
#endif
 
#ifdef GL_COMPRESSED_RG 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_COLOR_ENCODING 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_COMPONENT_TYPE 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_RED_SIZE 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_GREEN_SIZE 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_BLUE_SIZE 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_ALPHA_SIZE 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_DEPTH_SIZE 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_STENCIL_SIZE 
#endif
 
#ifdef GL_FRAMEBUFFER_DEFAULT 
#endif
 
#ifdef GL_FRAMEBUFFER_UNDEFINED 
#endif
 
#ifdef GL_DEPTH_STENCIL_ATTACHMENT 
#endif
 
#ifdef GL_INDEX 
#endif
 
#ifdef GL_RG 
#endif
 
#ifdef GL_RG_INTEGER 
#endif
 
#ifdef GL_R8 
#endif
 
#ifdef GL_R16 
#endif
 
#ifdef GL_RG8 
#endif
 
#ifdef GL_RG16 
#endif
 
#ifdef GL_R16F 
#endif
 
#ifdef GL_R32F 
#endif
 
#ifdef GL_RG16F 
#endif
 
#ifdef GL_RG32F 
#endif
 
#ifdef GL_R8I 
#endif
 
#ifdef GL_R8UI 
#endif
 
#ifdef GL_R16I 
#endif
 
#ifdef GL_R16UI 
#endif
 
#ifdef GL_R32I 
#endif
 
#ifdef GL_R32UI 
#endif
 
#ifdef GL_RG8I 
#endif
 
#ifdef GL_RG8UI 
#endif
 
#ifdef GL_RG16I 
#endif
 
#ifdef GL_RG16UI 
#endif
 
#ifdef GL_RG32I 
#endif
 
#ifdef GL_RG32UI 
#endif

/**
 
 * ContextFlagMask 
 * ----------------------------------------------------------------------------
 * Should be shared with WGL/GLX, but aren't since the FORWARD_COMPATIBLE and
 * DEBUG values are swapped vs. WGL/GLX. 
 */
 
#ifdef GL_CONTEXT_FLAG_FORWARD_COMPATIBLE_BIT 
#endif

/**
 * @vendor NV
 * For Pat Brown 2005/10/13 
 */
 
#ifdef GL_RGBA32UI 
#endif
 
#ifdef GL_RGB32UI 
#endif
 
#ifdef GL_RGBA16UI 
#endif
 
#ifdef GL_RGB16UI 
#endif
 
#ifdef GL_RGBA8UI 
#endif
 
#ifdef GL_RGB8UI 
#endif
 
#ifdef GL_RGBA32I 
#endif
 
#ifdef GL_RGB32I 
#endif
 
#ifdef GL_RGBA16I 
#endif
 
#ifdef GL_RGB16I 
#endif
 
#ifdef GL_RGBA8I 
#endif
 
#ifdef GL_RGB8I 
#endif
 
#ifdef GL_RED_INTEGER 
#endif
 
#ifdef GL_GREEN_INTEGER 
#endif
 
#ifdef GL_BLUE_INTEGER 
#endif
 
#ifdef GL_RGB_INTEGER 
#endif
 
#ifdef GL_RGBA_INTEGER 
#endif
 
#ifdef GL_BGR_INTEGER 
#endif
 
#ifdef GL_BGRA_INTEGER 
#endif
 
#ifdef GL_SAMPLER_1D_ARRAY 
#endif
 
#ifdef GL_SAMPLER_2D_ARRAY 
#endif
 
#ifdef GL_SAMPLER_1D_ARRAY_SHADOW 
#endif
 
#ifdef GL_SAMPLER_2D_ARRAY_SHADOW 
#endif
 
#ifdef GL_SAMPLER_CUBE_SHADOW 
#endif
 
#ifdef GL_UNSIGNED_INT_VEC2 
#endif
 
#ifdef GL_UNSIGNED_INT_VEC3 
#endif
 
#ifdef GL_UNSIGNED_INT_VEC4 
#endif
 
#ifdef GL_INT_SAMPLER_1D 
#endif
 
#ifdef GL_INT_SAMPLER_2D 
#endif
 
#ifdef GL_INT_SAMPLER_3D 
#endif
 
#ifdef GL_INT_SAMPLER_CUBE 
#endif
 
#ifdef GL_INT_SAMPLER_1D_ARRAY 
#endif
 
#ifdef GL_INT_SAMPLER_2D_ARRAY 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_1D 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_2D 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_3D 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_CUBE 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_1D_ARRAY 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_2D_ARRAY 
#endif
 
#ifdef GL_FLOAT_32_UNSIGNED_INT_24_8_REV 
#endif
 
#ifdef GL_FRAMEBUFFER_SRGB 
#endif
 
#ifdef GL_COMPRESSED_RED_RGTC1 
#endif
 
#ifdef GL_COMPRESSED_SIGNED_RED_RGTC1 
#endif
 
#ifdef GL_COMPRESSED_RG_RGTC2 
#endif
 
#ifdef GL_COMPRESSED_SIGNED_RG_RGTC2 
#endif
 
#ifdef GL_ALPHA_INTEGER 
#endif
 
#ifdef GL_SAMPLER_BUFFER 
#endif
 
#ifdef GL_INT_SAMPLER_2D_RECT 
#endif
 
#ifdef GL_INT_SAMPLER_BUFFER 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_2D_RECT 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_BUFFER 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_LAYERED 
#endif
 
#ifdef GL_FRAMEBUFFER_INCOMPLETE_LAYER_TARGETS 
#endif
 
#ifdef GL_GEOMETRY_SHADER 
#endif
 
#ifdef GL_MAX_GEOMETRY_UNIFORM_COMPONENTS 
#endif
 
#ifdef GL_MAX_GEOMETRY_OUTPUT_VERTICES 
#endif
 
#ifdef GL_MAX_GEOMETRY_TOTAL_OUTPUT_COMPONENTS 
#endif
 
#ifdef GL_INT_2_10_10_10_REV 
#endif
 
#ifdef GL_ACTIVE_SUBROUTINES 
#endif
 
#ifdef GL_ACTIVE_SUBROUTINE_UNIFORMS 
#endif
 
#ifdef GL_MAX_SUBROUTINES 
#endif
 
#ifdef GL_MAX_SUBROUTINE_UNIFORM_LOCATIONS 
#endif

/**
 * @vendor NV
 * For Michael Gold 2006/08/07 
 */
 
#ifdef GL_QUERY_WAIT 
#endif
 
#ifdef GL_QUERY_NO_WAIT 
#endif
 
#ifdef GL_QUERY_BY_REGION_WAIT 
#endif
 
#ifdef GL_QUERY_BY_REGION_NO_WAIT 
#endif
 
#ifdef GL_QUADS_FOLLOW_PROVOKING_VERTEX_CONVENTION 
#endif
 
#ifdef GL_FIRST_VERTEX_CONVENTION 
#endif
 
#ifdef GL_LAST_VERTEX_CONVENTION 
#endif
 
#ifdef GL_PROVOKING_VERTEX 
#endif
 
#ifdef GL_SAMPLE_POSITION 
#endif
 
#ifdef GL_SAMPLE_MASK 
#endif
 
#ifdef GL_SAMPLE_MASK_VALUE 
#endif
 
#ifdef GL_MAX_SAMPLE_MASK_WORDS 
#endif
 
#ifdef GL_TEXTURE_SWIZZLE_R 
#endif
 
#ifdef GL_TEXTURE_SWIZZLE_G 
#endif
 
#ifdef GL_TEXTURE_SWIZZLE_B 
#endif
 
#ifdef GL_TEXTURE_SWIZZLE_A 
#endif
 
#ifdef GL_TEXTURE_SWIZZLE_RGBA 
#endif
 
#ifdef GL_TIMESTAMP 
#endif
 
#ifdef GL_MIN_PROGRAM_TEXTURE_GATHER_OFFSET 
#endif
 
#ifdef GL_MAX_PROGRAM_TEXTURE_GATHER_OFFSET 
#endif
 
#ifdef GL_MAX_GEOMETRY_SHADER_INVOCATIONS 
#endif
 
#ifdef GL_MIN_FRAGMENT_INTERPOLATION_OFFSET 
#endif
 
#ifdef GL_MAX_FRAGMENT_INTERPOLATION_OFFSET 
#endif
 
#ifdef GL_FRAGMENT_INTERPOLATION_OFFSET_BITS 
#endif
 
#ifdef GL_MAX_VERTEX_STREAMS 
#endif
 
#ifdef GL_ACTIVE_SUBROUTINE_UNIFORM_LOCATIONS 
#endif
 
#ifdef GL_ACTIVE_SUBROUTINE_MAX_LENGTH 
#endif
 
#ifdef GL_ACTIVE_SUBROUTINE_UNIFORM_MAX_LENGTH 
#endif
 
#ifdef GL_NUM_COMPATIBLE_SUBROUTINES 
#endif
 
#ifdef GL_COMPATIBLE_SUBROUTINES 
#endif
 
#ifdef GL_PATCH_VERTICES 
#endif
 
#ifdef GL_PATCH_DEFAULT_INNER_LEVEL 
#endif
 
#ifdef GL_PATCH_DEFAULT_OUTER_LEVEL 
#endif
 
#ifdef GL_TESS_CONTROL_OUTPUT_VERTICES 
#endif
 
#ifdef GL_TESS_GEN_MODE 
#endif
 
#ifdef GL_TESS_GEN_SPACING 
#endif
 
#ifdef GL_TESS_GEN_VERTEX_ORDER 
#endif
 
#ifdef GL_TESS_GEN_POINT_MODE 
#endif
 
#ifdef GL_ISOLINES 
#endif
 
#ifdef GL_FRACTIONAL_ODD 
#endif
 
#ifdef GL_FRACTIONAL_EVEN 
#endif
 
#ifdef GL_MAX_PATCH_VERTICES 
#endif
 
#ifdef GL_MAX_TESS_GEN_LEVEL 
#endif
 
#ifdef GL_MAX_TESS_CONTROL_UNIFORM_COMPONENTS 
#endif
 
#ifdef GL_MAX_TESS_EVALUATION_UNIFORM_COMPONENTS 
#endif
 
#ifdef GL_MAX_TESS_CONTROL_TEXTURE_IMAGE_UNITS 
#endif
 
#ifdef GL_MAX_TESS_EVALUATION_TEXTURE_IMAGE_UNITS 
#endif
 
#ifdef GL_MAX_TESS_CONTROL_OUTPUT_COMPONENTS 
#endif
 
#ifdef GL_MAX_TESS_PATCH_COMPONENTS 
#endif
 
#ifdef GL_MAX_TESS_CONTROL_TOTAL_OUTPUT_COMPONENTS 
#endif
 
#ifdef GL_MAX_TESS_EVALUATION_OUTPUT_COMPONENTS 
#endif
 
#ifdef GL_MAX_TESS_CONTROL_UNIFORM_BLOCKS 
#endif
 
#ifdef GL_MAX_TESS_EVALUATION_UNIFORM_BLOCKS 
#endif
 
#ifdef GL_MAX_COMBINED_TESS_CONTROL_UNIFORM_COMPONENTS 
#endif
 
#ifdef GL_MAX_COMBINED_TESS_EVALUATION_UNIFORM_COMPONENTS 
#endif
 
#ifdef GL_TESS_EVALUATION_SHADER 
#endif
 
#ifdef GL_TESS_CONTROL_SHADER 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_BUFFER_PAUSED 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_BUFFER_ACTIVE 
#endif
 
#ifdef GL_TRANSFORM_FEEDBACK_BINDING 
#endif
 
#ifdef GL_MAX_TRANSFORM_FEEDBACK_BUFFERS 
#endif

/**
 * @vendor ARB 
 */
 
#ifdef GL_BUFFER_ACCESS_FLAGS 
#endif
 
#ifdef GL_BUFFER_MAP_LENGTH 
#endif
 
#ifdef GL_BUFFER_MAP_OFFSET 
#endif
 
#ifdef GL_MAX_VERTEX_OUTPUT_COMPONENTS 
#endif
 
#ifdef GL_MAX_GEOMETRY_INPUT_COMPONENTS 
#endif
 
#ifdef GL_MAX_GEOMETRY_OUTPUT_COMPONENTS 
#endif
 
#ifdef GL_MAX_FRAGMENT_INPUT_COMPONENTS 
#endif
 
#ifdef GL_CONTEXT_PROFILE_MASK 
#endif
 
#ifdef GL_MAX_SERVER_WAIT_TIMEOUT 
#endif
 
#ifdef GL_OBJECT_TYPE 
#endif
 
#ifdef GL_SYNC_CONDITION 
#endif
 
#ifdef GL_SYNC_STATUS 
#endif
 
#ifdef GL_SYNC_FLAGS 
#endif
 
#ifdef GL_SYNC_FENCE 
#endif
 
#ifdef GL_SYNC_GPU_COMMANDS_COMPLETE 
#endif
 
#ifdef GL_UNSIGNALED 
#endif
 
#ifdef GL_SIGNALED 
#endif
 
#ifdef GL_ALREADY_SIGNALED 
#endif
 
#ifdef GL_TIMEOUT_EXPIRED 
#endif
 
#ifdef GL_CONDITION_SATISFIED 
#endif
 
#ifdef GL_WAIT_FAILED 
#endif
 
#ifdef GL_TEXTURE_2D_MULTISAMPLE 
#endif
 
#ifdef GL_PROXY_TEXTURE_2D_MULTISAMPLE 
#endif
 
#ifdef GL_TEXTURE_2D_MULTISAMPLE_ARRAY 
#endif
 
#ifdef GL_PROXY_TEXTURE_2D_MULTISAMPLE_ARRAY 
#endif
 
#ifdef GL_TEXTURE_BINDING_2D_MULTISAMPLE 
#endif
 
#ifdef GL_TEXTURE_BINDING_2D_MULTISAMPLE_ARRAY 
#endif
 
#ifdef GL_TEXTURE_SAMPLES 
#endif
 
#ifdef GL_TEXTURE_FIXED_SAMPLE_LOCATIONS 
#endif
 
#ifdef GL_SAMPLER_2D_MULTISAMPLE 
#endif
 
#ifdef GL_INT_SAMPLER_2D_MULTISAMPLE 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_2D_MULTISAMPLE 
#endif
 
#ifdef GL_SAMPLER_2D_MULTISAMPLE_ARRAY 
#endif
 
#ifdef GL_INT_SAMPLER_2D_MULTISAMPLE_ARRAY 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_2D_MULTISAMPLE_ARRAY 
#endif
 
#ifdef GL_MAX_COLOR_TEXTURE_SAMPLES 
#endif
 
#ifdef GL_MAX_DEPTH_TEXTURE_SAMPLES 
#endif
 
#ifdef GL_MAX_INTEGER_SAMPLES 
#endif

/**
 * @vendor ARB
 * Framebuffer object specification + headroom 
 */
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_OBJECT_TYPE 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_OBJECT_NAME 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_TEXTURE_LEVEL 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_TEXTURE_CUBE_MAP_FACE 
#endif
 
#ifdef GL_FRAMEBUFFER_ATTACHMENT_TEXTURE_LAYER 
#endif
 
#ifdef GL_FRAMEBUFFER_COMPLETE 
#endif
 
#ifdef GL_FRAMEBUFFER_INCOMPLETE_ATTACHMENT 
#endif
 
#ifdef GL_FRAMEBUFFER_INCOMPLETE_MISSING_ATTACHMENT 
#endif
 
#ifdef GL_FRAMEBUFFER_INCOMPLETE_DRAW_BUFFER 
#endif
 
#ifdef GL_FRAMEBUFFER_INCOMPLETE_READ_BUFFER 
#endif
 
#ifdef GL_FRAMEBUFFER_UNSUPPORTED 
#endif
 
#ifdef GL_MAX_COLOR_ATTACHMENTS 
#endif
 
#ifdef GL_COLOR_ATTACHMENT0 
#endif
 
#ifdef GL_COLOR_ATTACHMENT1 
#endif
 
#ifdef GL_COLOR_ATTACHMENT2 
#endif
 
#ifdef GL_COLOR_ATTACHMENT3 
#endif
 
#ifdef GL_COLOR_ATTACHMENT4 
#endif
 
#ifdef GL_COLOR_ATTACHMENT5 
#endif
 
#ifdef GL_COLOR_ATTACHMENT6 
#endif
 
#ifdef GL_COLOR_ATTACHMENT7 
#endif
 
#ifdef GL_COLOR_ATTACHMENT8 
#endif
 
#ifdef GL_COLOR_ATTACHMENT9 
#endif
 
#ifdef GL_COLOR_ATTACHMENT10 
#endif
 
#ifdef GL_COLOR_ATTACHMENT11 
#endif
 
#ifdef GL_COLOR_ATTACHMENT12 
#endif
 
#ifdef GL_COLOR_ATTACHMENT13 
#endif
 
#ifdef GL_COLOR_ATTACHMENT14 
#endif
 
#ifdef GL_COLOR_ATTACHMENT15 
#endif
 
#ifdef GL_COLOR_ATTACHMENT16 
#endif
 
#ifdef GL_COLOR_ATTACHMENT17 
#endif
 
#ifdef GL_COLOR_ATTACHMENT18 
#endif
 
#ifdef GL_COLOR_ATTACHMENT19 
#endif
 
#ifdef GL_COLOR_ATTACHMENT20 
#endif
 
#ifdef GL_COLOR_ATTACHMENT21 
#endif
 
#ifdef GL_COLOR_ATTACHMENT22 
#endif
 
#ifdef GL_COLOR_ATTACHMENT23 
#endif
 
#ifdef GL_COLOR_ATTACHMENT24 
#endif
 
#ifdef GL_COLOR_ATTACHMENT25 
#endif
 
#ifdef GL_COLOR_ATTACHMENT26 
#endif
 
#ifdef GL_COLOR_ATTACHMENT27 
#endif
 
#ifdef GL_COLOR_ATTACHMENT28 
#endif
 
#ifdef GL_COLOR_ATTACHMENT29 
#endif
 
#ifdef GL_COLOR_ATTACHMENT30 
#endif
 
#ifdef GL_COLOR_ATTACHMENT31 
#endif
 
#ifdef GL_DEPTH_ATTACHMENT 
#endif
 
#ifdef GL_STENCIL_ATTACHMENT 
#endif
 
#ifdef GL_FRAMEBUFFER 
#endif
 
#ifdef GL_RENDERBUFFER 
#endif
 
#ifdef GL_RENDERBUFFER_WIDTH 
#endif
 
#ifdef GL_RENDERBUFFER_HEIGHT 
#endif
 
#ifdef GL_RENDERBUFFER_INTERNAL_FORMAT 
#endif
 
#ifdef GL_STENCIL_INDEX1 
#endif
 
#ifdef GL_STENCIL_INDEX4 
#endif
 
#ifdef GL_STENCIL_INDEX8 
#endif
 
#ifdef GL_STENCIL_INDEX16 
#endif
 
#ifdef GL_RENDERBUFFER_RED_SIZE 
#endif
 
#ifdef GL_RENDERBUFFER_GREEN_SIZE 
#endif
 
#ifdef GL_RENDERBUFFER_BLUE_SIZE 
#endif
 
#ifdef GL_RENDERBUFFER_ALPHA_SIZE 
#endif
 
#ifdef GL_RENDERBUFFER_DEPTH_SIZE 
#endif
 
#ifdef GL_RENDERBUFFER_STENCIL_SIZE 
#endif
 
#ifdef GL_FRAMEBUFFER_INCOMPLETE_MULTISAMPLE 
#endif
 
#ifdef GL_MAX_SAMPLES 
#endif

/**
 
 * MapBufferAccessMask 
 * ----------------------------------------------------------------------------
 *  
 */
 
#ifdef GL_MAP_READ_BIT 
#endif
 
#ifdef GL_MAP_WRITE_BIT 
#endif
 
#ifdef GL_MAP_INVALIDATE_RANGE_BIT 
#endif
 
#ifdef GL_MAP_INVALIDATE_BUFFER_BIT 
#endif
 
#ifdef GL_MAP_FLUSH_EXPLICIT_BIT 
#endif
 
#ifdef GL_MAP_UNSYNCHRONIZED_BIT 
#endif

/**
 * @vendor APPLE 
 */
 
#ifdef GL_VERTEX_ARRAY_BINDING 
#endif

/**
 * @vendor ARB 
 */
 
#ifdef GL_R8_SNORM 
#endif
 
#ifdef GL_RG8_SNORM 
#endif
 
#ifdef GL_RGB8_SNORM 
#endif
 
#ifdef GL_RGBA8_SNORM 
#endif
 
#ifdef GL_R16_SNORM 
#endif
 
#ifdef GL_RG16_SNORM 
#endif
 
#ifdef GL_RGB16_SNORM 
#endif
 
#ifdef GL_RGBA16_SNORM 
#endif
 
#ifdef GL_SIGNED_NORMALIZED 
#endif
 
#ifdef GL_PRIMITIVE_RESTART 
#endif
 
#ifdef GL_PRIMITIVE_RESTART_INDEX 
#endif

/**
 * @vendor NV
 * For Pat Brown, Khronos bug 3191 
 */
 
#ifdef GL_COPY_READ_BUFFER 
#endif
 
#ifdef GL_COPY_WRITE_BUFFER 
#endif
 
#ifdef GL_DRAW_INDIRECT_BUFFER 
#endif
 
#ifdef GL_DRAW_INDIRECT_BUFFER_BINDING 
#endif
 
#ifdef GL_DOUBLE_MAT2 
#endif
 
#ifdef GL_DOUBLE_MAT3 
#endif
 
#ifdef GL_DOUBLE_MAT4 
#endif
 
#ifdef GL_DOUBLE_MAT2x3 
#endif
 
#ifdef GL_DOUBLE_MAT2x4 
#endif
 
#ifdef GL_DOUBLE_MAT3x2 
#endif
 
#ifdef GL_DOUBLE_MAT3x4 
#endif
 
#ifdef GL_DOUBLE_MAT4x2 
#endif
 
#ifdef GL_DOUBLE_MAT4x3 
#endif

/**
 * @vendor APPLE 
 */
 
#ifdef GL_UNIFORM_BUFFER 
#endif
 
#ifdef GL_UNIFORM_BUFFER_BINDING 
#endif
 
#ifdef GL_UNIFORM_BUFFER_START 
#endif
 
#ifdef GL_UNIFORM_BUFFER_SIZE 
#endif
 
#ifdef GL_MAX_VERTEX_UNIFORM_BLOCKS 
#endif
 
#ifdef GL_MAX_GEOMETRY_UNIFORM_BLOCKS 
#endif
 
#ifdef GL_MAX_FRAGMENT_UNIFORM_BLOCKS 
#endif
 
#ifdef GL_MAX_COMBINED_UNIFORM_BLOCKS 
#endif
 
#ifdef GL_MAX_UNIFORM_BUFFER_BINDINGS 
#endif
 
#ifdef GL_MAX_UNIFORM_BLOCK_SIZE 
#endif
 
#ifdef GL_MAX_COMBINED_VERTEX_UNIFORM_COMPONENTS 
#endif
 
#ifdef GL_MAX_COMBINED_GEOMETRY_UNIFORM_COMPONENTS 
#endif
 
#ifdef GL_MAX_COMBINED_FRAGMENT_UNIFORM_COMPONENTS 
#endif
 
#ifdef GL_UNIFORM_BUFFER_OFFSET_ALIGNMENT 
#endif
 
#ifdef GL_ACTIVE_UNIFORM_BLOCK_MAX_NAME_LENGTH 
#endif
 
#ifdef GL_ACTIVE_UNIFORM_BLOCKS 
#endif
 
#ifdef GL_UNIFORM_TYPE 
#endif
 
#ifdef GL_UNIFORM_SIZE 
#endif
 
#ifdef GL_UNIFORM_NAME_LENGTH 
#endif
 
#ifdef GL_UNIFORM_BLOCK_INDEX 
#endif
 
#ifdef GL_UNIFORM_OFFSET 
#endif
 
#ifdef GL_UNIFORM_ARRAY_STRIDE 
#endif
 
#ifdef GL_UNIFORM_MATRIX_STRIDE 
#endif
 
#ifdef GL_UNIFORM_IS_ROW_MAJOR 
#endif
 
#ifdef GL_UNIFORM_BLOCK_BINDING 
#endif
 
#ifdef GL_UNIFORM_BLOCK_DATA_SIZE 
#endif
 
#ifdef GL_UNIFORM_BLOCK_NAME_LENGTH 
#endif
 
#ifdef GL_UNIFORM_BLOCK_ACTIVE_UNIFORMS 
#endif
 
#ifdef GL_UNIFORM_BLOCK_ACTIVE_UNIFORM_INDICES 
#endif
 
#ifdef GL_UNIFORM_BLOCK_REFERENCED_BY_VERTEX_SHADER 
#endif
 
#ifdef GL_UNIFORM_BLOCK_REFERENCED_BY_GEOMETRY_SHADER 
#endif
 
#ifdef GL_UNIFORM_BLOCK_REFERENCED_BY_FRAGMENT_SHADER 
#endif

/**
 
 * ContextProfileMask 
 * ----------------------------------------------------------------------------
 *  
 */
 
#ifdef GL_CONTEXT_CORE_PROFILE_BIT 
#endif
 
#ifdef GL_CONTEXT_COMPATIBILITY_PROFILE_BIT 
#endif

/**
 
 * SyncObjectMask 
 * ----------------------------------------------------------------------------
 *  
 */
 
#ifdef GL_SYNC_FLUSH_COMMANDS_BIT 
#endif

/**
 * @vendor NV
 * For Pat Brown, bug 4935 
 */
 
#ifdef GL_RGB10_A2UI 
#endif

/**
 * @vendor AMD
 * For Bill Licea-Kane 
 */
 
#ifdef GL_TEXTURE_CUBE_MAP_ARRAY 
#endif
 
#ifdef GL_TEXTURE_BINDING_CUBE_MAP_ARRAY 
#endif
 
#ifdef GL_PROXY_TEXTURE_CUBE_MAP_ARRAY 
#endif
 
#ifdef GL_SAMPLER_CUBE_MAP_ARRAY 
#endif
 
#ifdef GL_SAMPLER_CUBE_MAP_ARRAY_SHADOW 
#endif
 
#ifdef GL_INT_SAMPLER_CUBE_MAP_ARRAY 
#endif
 
#ifdef GL_UNSIGNED_INT_SAMPLER_CUBE_MAP_ARRAY 
#endif

/**
 * @vendor NV
 * For Pat Brown, bug 4935 
 */
 
#ifdef GL_DOUBLE_VEC2 
#endif
 
#ifdef GL_DOUBLE_VEC3 
#endif
 
#ifdef GL_DOUBLE_VEC4 
#endif

/**
 * @vendor OES 
 */
 
#ifdef GL_IMPLEMENTATION_COLOR_READ_TYPE 
#endif
 
#ifdef GL_IMPLEMENTATION_COLOR_READ_FORMAT 
#endif

/**
 * @vendor OES 
 */
 
#ifdef GL_LOW_FLOAT 
#endif
 
#ifdef GL_MEDIUM_FLOAT 
#endif
 
#ifdef GL_HIGH_FLOAT 
#endif
 
#ifdef GL_LOW_INT 
#endif
 
#ifdef GL_MEDIUM_INT 
#endif
 
#ifdef GL_HIGH_INT 
#endif
 
#ifdef GL_SHADER_COMPILER 
#endif
 
#ifdef GL_SHADER_BINARY_FORMATS 
#endif
 
#ifdef GL_NUM_SHADER_BINARY_FORMATS 
#endif
 
#ifdef GL_MAX_VERTEX_UNIFORM_VECTORS 
#endif
 
#ifdef GL_MAX_VARYING_VECTORS 
#endif
 
#ifdef GL_MAX_FRAGMENT_UNIFORM_VECTORS 
#endif

/**
 * @vendor OES 
 */
 
#ifdef GL_RGB565 
#endif

/**
 * @vendor ARB
 * Range released by MS on 2002/9/16 
 */
 
#ifdef GL_PROGRAM_BINARY_RETRIEVABLE_HINT 
#endif
 
#ifdef GL_PROGRAM_SEPARABLE 
#endif
 
#ifdef GL_ACTIVE_PROGRAM 
#endif
 
#ifdef GL_PROGRAM_PIPELINE_BINDING 
#endif
 
#ifdef GL_MAX_VIEWPORTS 
#endif
 
#ifdef GL_VIEWPORT_SUBPIXEL_BITS 
#endif
 
#ifdef GL_VIEWPORT_BOUNDS_RANGE 
#endif
 
#ifdef GL_LAYER_PROVOKING_VERTEX 
#endif
 
#ifdef GL_VIEWPORT_INDEX_PROVOKING_VERTEX 
#endif
 
#ifdef GL_UNDEFINED_VERTEX 
#endif

/**
 * @vendor AMD 
 */
 
#ifdef GL_PROGRAM_BINARY_LENGTH 
#endif

/**
 
 * UseProgramStageMask 
 * ----------------------------------------------------------------------------
 *  
 */
 
#ifdef GL_VERTEX_SHADER_BIT 
#endif
 
#ifdef GL_FRAGMENT_SHADER_BIT 
#endif
 
#ifdef GL_GEOMETRY_SHADER_BIT 
#endif
 
#ifdef GL_TESS_CONTROL_SHADER_BIT 
#endif
 
#ifdef GL_TESS_EVALUATION_SHADER_BIT 
#endif
 
#ifdef GL_ALL_SHADER_BITS 
#endif

#endif
