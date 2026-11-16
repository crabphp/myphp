/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: a884d4446c3abe21e7c2c25cbd52076ecd06f31e */

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Texture_Texture2D_fromDisk, 0, 1, GL\\Texture\\Texture2D, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, requestedChannelCount, IS_LONG, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, flipVertically, _IS_BOOL, 0, "true")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Texture_Texture2D_fromBuffer, 0, 3, GL\\Texture\\Texture2D, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\\125ByteBuffer, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, channels, IS_LONG, 0, "GL\\Texture\\Texture2D::CHANNEL_RGBA")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Texture_Texture2D_fromBufferHDR, 0, 3, GL\\Texture\\Texture2D, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\FloatBuffer, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, channels, IS_LONG, 0, "GL\\Texture\\Texture2D::CHANNEL_RGBA")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_TYPE_MASK_EX(arginfo_class_GL_Texture_Texture2D_buffer, 0, 0, GL\\Buffer\\\125ByteBuffer|GL\\Buffer\\FloatBuffer, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Texture_Texture2D_width, 0, 0, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Texture_Texture2D_height arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Texture_Texture2D_channels arginfo_class_GL_Texture_Texture2D_width

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Texture_Texture2D_isHDR, 0, 0, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Texture_Texture2D_writeJPG, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, quality, IS_LONG, 0, "100")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Texture_Texture2D_writePNG, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Texture_Texture2D_writeBMP arginfo_class_GL_Texture_Texture2D_writePNG

#define arginfo_class_GL_Texture_Texture2D_writeTGA arginfo_class_GL_Texture_Texture2D_writePNG

#define arginfo_class_GL_Texture_Texture2D_writeHDR arginfo_class_GL_Texture_Texture2D_writePNG

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Noise_perlin, 0, 3, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, z, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, wrapX, IS_LONG, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, wrapY, IS_LONG, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, wrapZ, IS_LONG, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, seed, IS_LONG, 0, "0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Noise_ridge, 0, 3, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, z, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, lacunarity, IS_DOUBLE, 0, "2.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, gain, IS_DOUBLE, 0, "0.5")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, offset, IS_DOUBLE, 0, "1.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, octaves, IS_LONG, 0, "6")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Noise_fbm, 0, 3, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, z, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, lacunarity, IS_DOUBLE, 0, "2.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, gain, IS_DOUBLE, 0, "0.5")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, octaves, IS_LONG, 0, "6")
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Noise_turbulence arginfo_class_GL_Noise_fbm

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Noise_perlinFill2D, 0, 4, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\FloatBuffer, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, scale, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, offsetX, IS_DOUBLE, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, offsetY, IS_DOUBLE, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, wrapX, IS_LONG, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, wrapY, IS_LONG, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, seed, IS_LONG, 0, "0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Noise_ridgeFill2D, 0, 4, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\FloatBuffer, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, scale, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, offsetX, IS_DOUBLE, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, offsetY, IS_DOUBLE, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, lacunarity, IS_DOUBLE, 0, "2.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, gain, IS_DOUBLE, 0, "0.5")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, offset, IS_DOUBLE, 0, "1.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, octaves, IS_LONG, 0, "6")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Noise_fbmFill2D, 0, 4, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\FloatBuffer, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, scale, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, offsetX, IS_DOUBLE, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, offsetY, IS_DOUBLE, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, lacunarity, IS_DOUBLE, 0, "2.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, gain, IS_DOUBLE, 0, "0.5")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, octaves, IS_LONG, 0, "6")
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Noise_turbulenceFill2D arginfo_class_GL_Noise_fbmFill2D

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Noise_islandFill2D, 0, 3, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\FloatBuffer, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, islandseed, IS_LONG, 0, "42")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, scale, IS_DOUBLE, 0, "1.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, islandmix, IS_DOUBLE, 0, "0.7")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, lacunarity, IS_DOUBLE, 0, "2.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, gain, IS_DOUBLE, 0, "0.5")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, octaves, IS_LONG, 0, "6")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_GLM_radians, 0, 1, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, degrees, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_GLM_angle, 0, 1, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radians, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_GLM_triangleNormal, 0, 3, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, p1, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, p2, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, p3, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_TYPE_MASK_EX(arginfo_class_GL_Math_GLM_normalize, 0, 1, GL\\Math\\Vec2|GL\\Math\\Vec3|GL\\Math\\Vec4, 0)
	ZEND_ARG_OBJ_TYPE_MASK(0, vec, GL\\Math\\Vec2|GL\\Math\\Vec3|GL\\Math\\Vec4, 0, NULL)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_GL_Math_Vec2___construct, 0, 0, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, x, IS_DOUBLE, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, y, IS_DOUBLE, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec2_copy, 0, 0, GL\\Math\\Vec2, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec2_normalized, 0, 1, GL\\Math\\Vec2, 0)
	ZEND_ARG_OBJ_INFO(0, vec, GL\\Math\\Vec2, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Vec2_distance, 0, 2, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Vec2, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Vec2, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec2_distance2 arginfo_class_GL_Math_Vec2_distance

#define arginfo_class_GL_Math_Vec2_dot arginfo_class_GL_Math_Vec2_distance

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec2_mix, 0, 3, GL\\Math\\Vec2, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Vec2, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Vec2, 0)
	ZEND_ARG_TYPE_INFO(0, t, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec2_lerp arginfo_class_GL_Math_Vec2_mix

#define arginfo_class_GL_Math_Vec2_slerp arginfo_class_GL_Math_Vec2_mix

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Vec2_length, 0, 0, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Vec2_distanceTo, 0, 1, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Vec2, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec2_distance2To arginfo_class_GL_Math_Vec2_distanceTo

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Vec2_normalize, 0, 0, IS_VOID, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec2_abs arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Vec2___toString, 0, 0, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_GL_Math_Vec3___construct, 0, 0, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, x, IS_DOUBLE, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, y, IS_DOUBLE, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, z, IS_DOUBLE, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec3_copy, 0, 0, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec3_normalized, 0, 1, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, vec, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Vec3_distance, 0, 2, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec3_distance2 arginfo_class_GL_Math_Vec3_distance

#define arginfo_class_GL_Math_Vec3_dot arginfo_class_GL_Math_Vec3_distance

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec3_mix, 0, 3, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Vec3, 0)
	ZEND_ARG_TYPE_INFO(0, t, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec3_lerp arginfo_class_GL_Math_Vec3_mix

#define arginfo_class_GL_Math_Vec3_slerp arginfo_class_GL_Math_Vec3_mix

#define arginfo_class_GL_Math_Vec3_length arginfo_class_GL_Math_Vec2_length

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Vec3_distanceTo, 0, 1, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec3_distance2To arginfo_class_GL_Math_Vec3_distanceTo

#define arginfo_class_GL_Math_Vec3_normalize arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Math_Vec3_abs arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec3_cross, 0, 2, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec3_multiplyQuat, 0, 2, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Quat, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec3___toString arginfo_class_GL_Math_Vec2___toString

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_GL_Math_Vec4___construct, 0, 0, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, x, IS_DOUBLE, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, y, IS_DOUBLE, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, z, IS_DOUBLE, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, w, IS_DOUBLE, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec4_copy, 0, 0, GL\\Math\\Vec4, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec4_normalized, 0, 1, GL\\Math\\Vec4, 0)
	ZEND_ARG_OBJ_INFO(0, vec, GL\\Math\\Vec4, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Vec4_distance, 0, 2, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Vec4, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Vec4, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec4_distance2 arginfo_class_GL_Math_Vec4_distance

#define arginfo_class_GL_Math_Vec4_dot arginfo_class_GL_Math_Vec4_distance

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Vec4_mix, 0, 3, GL\\Math\\Vec4, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Vec4, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Vec4, 0)
	ZEND_ARG_TYPE_INFO(0, t, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec4_lerp arginfo_class_GL_Math_Vec4_mix

#define arginfo_class_GL_Math_Vec4_slerp arginfo_class_GL_Math_Vec4_mix

#define arginfo_class_GL_Math_Vec4_length arginfo_class_GL_Math_Vec2_length

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Vec4_distanceTo, 0, 1, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Vec4, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Vec4_distance2To arginfo_class_GL_Math_Vec4_distanceTo

#define arginfo_class_GL_Math_Vec4_normalize arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Math_Vec4_abs arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Math_Vec4___toString arginfo_class_GL_Math_Vec2___toString

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_GL_Math_Quat___construct, 0, 0, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, w, IS_DOUBLE, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, x, IS_DOUBLE, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, y, IS_DOUBLE, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, z, IS_DOUBLE, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Quat_copy, 0, 0, GL\\Math\\Quat, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Quat_fromMat4, 0, 1, GL\\Math\\Quat, 0)
	ZEND_ARG_OBJ_INFO(0, matrix, GL\\Math\\Mat4, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Quat_fromVec4, 0, 1, GL\\Math\\Quat, 0)
	ZEND_ARG_OBJ_INFO(0, vec, GL\\Math\\Vec4, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Quat_inverted, 0, 1, GL\\Math\\Quat, 0)
	ZEND_ARG_OBJ_INFO(0, quat, GL\\Math\\Quat, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Quat_normalized arginfo_class_GL_Math_Quat_inverted

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Quat_mix, 0, 3, GL\\Math\\Quat, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Quat, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Quat, 0)
	ZEND_ARG_TYPE_INFO(0, t, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Quat_lerp arginfo_class_GL_Math_Quat_mix

#define arginfo_class_GL_Math_Quat_slerp arginfo_class_GL_Math_Quat_mix

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Quat_dot, 0, 2, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Quat, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Quat, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Quat_multiply, 0, 2, GL\\Math\\Quat, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Quat, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Quat, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Quat_multiplyVec3, 0, 2, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, quat, GL\\Math\\Quat, 0)
	ZEND_ARG_OBJ_INFO(0, vec, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Quat_multiplyMat4, 0, 2, GL\\Math\\Mat4, 0)
	ZEND_ARG_OBJ_INFO(0, quat, GL\\Math\\Quat, 0)
	ZEND_ARG_OBJ_INFO(0, mat, GL\\Math\\Mat4, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Quat_normalize arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Math_Quat_length arginfo_class_GL_Math_Vec2_length

#define arginfo_class_GL_Math_Quat_eulerAngles arginfo_class_GL_Math_Vec3_copy

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Quat_rotate, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, angle, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, axis, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Quat_inverse arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Quat_mat4, 0, 0, GL\\Math\\Mat4, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Quat___toString arginfo_class_GL_Math_Vec2___toString

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_GL_Math_Mat4___construct, 0, 0, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Mat4_copy arginfo_class_GL_Math_Quat_mat4

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Mat4_fromArray, 0, 1, GL\\Math\\Mat4, 0)
	ZEND_ARG_TYPE_INFO(0, values, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Mat4_inverted, 0, 1, GL\\Math\\Mat4, 0)
	ZEND_ARG_OBJ_INFO(0, matrix, GL\\Math\\Mat4, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Mat4_multiplyQuat, 0, 2, GL\\Math\\Mat4, 0)
	ZEND_ARG_OBJ_INFO(0, left, GL\\Math\\Mat4, 0)
	ZEND_ARG_OBJ_INFO(0, right, GL\\Math\\Quat, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Math_Mat4_row, 0, 1, GL\\Math\\Vec4, 0)
	ZEND_ARG_TYPE_INFO(0, index, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Mat4_setRow, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, index, IS_LONG, 0)
	ZEND_ARG_OBJ_INFO(0, row, GL\\Math\\Vec4, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Mat4_col arginfo_class_GL_Math_Mat4_row

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Mat4_setCol, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, index, IS_LONG, 0)
	ZEND_ARG_OBJ_INFO(0, col, GL\\Math\\Vec4, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Mat4_lookAt, 0, 3, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, eye, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, center, GL\\Math\\Vec3, 0)
	ZEND_ARG_OBJ_INFO(0, up, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Mat4_perspective, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, fov, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, aspect, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, near, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, far, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Mat4_ortho, 0, 6, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, left, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, right, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, bottom, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, top, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, near, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, far, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Mat4_transpose arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Math_Mat4_inverse arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Mat4_scale, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, scale, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Math_Mat4_translate, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, translation, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Math_Mat4_rotate arginfo_class_GL_Math_Quat_rotate

#define arginfo_class_GL_Math_Mat4_determinant arginfo_class_GL_Math_Vec2_length

#define arginfo_class_GL_Math_Mat4___toString arginfo_class_GL_Math_Vec2___toString

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_GL_Buffer_BufferInterface___construct, 0, 0, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, initalData, IS_ARRAY, 1, "null")
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_BufferInterface_clear arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Buffer_BufferInterface_size arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_BufferInterface_capacity arginfo_class_GL_Texture_Texture2D_width

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_BufferInterface_reserve, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, size, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_FloatBuffer___construct arginfo_class_GL_Buffer_BufferInterface___construct

#define arginfo_class_GL_Buffer_FloatBuffer___toString arginfo_class_GL_Math_Vec2___toString

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_FloatBuffer_push, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, value, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_FloatBuffer_pushArray, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, values, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_FloatBuffer_append, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\FloatBuffer, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_FloatBuffer_pushVec2, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, vec, GL\\Buffer\\GL\\Math\\Vec2, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_FloatBuffer_pushVec3, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, vec, GL\\Buffer\\GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_FloatBuffer_pushVec4, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, vec, GL\\Buffer\\GL\\Math\\Vec4, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_FloatBuffer_pushMat4, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, matrix, GL\\Buffer\\GL\\Math\\Mat4, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Buffer_FloatBuffer_quantizeToUChar, 0, 0, GL\\Buffer\\\125ByteBuffer, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, autoNormalize, _IS_BOOL, 0, "true")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, lowerBound, IS_DOUBLE, 0, "0.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, upperBound, IS_DOUBLE, 0, "1.0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_FloatBuffer_fill, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, count, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, value, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_FloatBuffer_clear arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Buffer_FloatBuffer_size arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_FloatBuffer_capacity arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_FloatBuffer_reserve arginfo_class_GL_Buffer_BufferInterface_reserve

#define arginfo_class_GL_Buffer_FloatBuffer_dump arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_HFloatBuffer___construct arginfo_class_GL_Buffer_BufferInterface___construct

#define arginfo_class_GL_Buffer_HFloatBuffer___toString arginfo_class_GL_Math_Vec2___toString

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_HFloatBuffer_push, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, value, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_HFloatBuffer_pushArray arginfo_class_GL_Buffer_FloatBuffer_pushArray

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_HFloatBuffer_append, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\HFloatBuffer, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_HFloatBuffer_fill, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, count, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, value, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_HFloatBuffer_clear arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Buffer_HFloatBuffer_size arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_HFloatBuffer_capacity arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_HFloatBuffer_reserve arginfo_class_GL_Buffer_BufferInterface_reserve

#define arginfo_class_GL_Buffer_HFloatBuffer_dump arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_DoubleBuffer___construct arginfo_class_GL_Buffer_BufferInterface___construct

#define arginfo_class_GL_Buffer_DoubleBuffer___toString arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_DoubleBuffer_push arginfo_class_GL_Buffer_FloatBuffer_push

#define arginfo_class_GL_Buffer_DoubleBuffer_pushArray arginfo_class_GL_Buffer_FloatBuffer_pushArray

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_DoubleBuffer_append, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\DoubleBuffer, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_DoubleBuffer_fill arginfo_class_GL_Buffer_FloatBuffer_fill

#define arginfo_class_GL_Buffer_DoubleBuffer_clear arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Buffer_DoubleBuffer_size arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_DoubleBuffer_capacity arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_DoubleBuffer_reserve arginfo_class_GL_Buffer_BufferInterface_reserve

#define arginfo_class_GL_Buffer_DoubleBuffer_dump arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_IntBuffer___construct arginfo_class_GL_Buffer_BufferInterface___construct

#define arginfo_class_GL_Buffer_IntBuffer___toString arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_IntBuffer_push arginfo_class_GL_Buffer_HFloatBuffer_push

#define arginfo_class_GL_Buffer_IntBuffer_pushArray arginfo_class_GL_Buffer_FloatBuffer_pushArray

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_IntBuffer_append, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\IntBuffer, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_IntBuffer_fill arginfo_class_GL_Buffer_HFloatBuffer_fill

#define arginfo_class_GL_Buffer_IntBuffer_clear arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Buffer_IntBuffer_size arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_IntBuffer_capacity arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_IntBuffer_reserve arginfo_class_GL_Buffer_BufferInterface_reserve

#define arginfo_class_GL_Buffer_IntBuffer_dump arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_UIntBuffer___construct arginfo_class_GL_Buffer_BufferInterface___construct

#define arginfo_class_GL_Buffer_UIntBuffer___toString arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_UIntBuffer_push arginfo_class_GL_Buffer_HFloatBuffer_push

#define arginfo_class_GL_Buffer_UIntBuffer_pushArray arginfo_class_GL_Buffer_FloatBuffer_pushArray

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_UIntBuffer_append, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\\125IntBuffer, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_UIntBuffer_fill arginfo_class_GL_Buffer_HFloatBuffer_fill

#define arginfo_class_GL_Buffer_UIntBuffer_clear arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Buffer_UIntBuffer_size arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_UIntBuffer_capacity arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_UIntBuffer_reserve arginfo_class_GL_Buffer_BufferInterface_reserve

#define arginfo_class_GL_Buffer_UIntBuffer_dump arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_ShortBuffer___construct arginfo_class_GL_Buffer_BufferInterface___construct

#define arginfo_class_GL_Buffer_ShortBuffer___toString arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_ShortBuffer_push arginfo_class_GL_Buffer_HFloatBuffer_push

#define arginfo_class_GL_Buffer_ShortBuffer_pushArray arginfo_class_GL_Buffer_FloatBuffer_pushArray

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_ShortBuffer_append, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\ShortBuffer, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_ShortBuffer_fill arginfo_class_GL_Buffer_HFloatBuffer_fill

#define arginfo_class_GL_Buffer_ShortBuffer_clear arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Buffer_ShortBuffer_size arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_ShortBuffer_capacity arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_ShortBuffer_reserve arginfo_class_GL_Buffer_BufferInterface_reserve

#define arginfo_class_GL_Buffer_ShortBuffer_dump arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_UShortBuffer___construct arginfo_class_GL_Buffer_BufferInterface___construct

#define arginfo_class_GL_Buffer_UShortBuffer___toString arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_UShortBuffer_push arginfo_class_GL_Buffer_HFloatBuffer_push

#define arginfo_class_GL_Buffer_UShortBuffer_pushArray arginfo_class_GL_Buffer_FloatBuffer_pushArray

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_UShortBuffer_append, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\\125ShortBuffer, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_UShortBuffer_fill arginfo_class_GL_Buffer_HFloatBuffer_fill

#define arginfo_class_GL_Buffer_UShortBuffer_clear arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Buffer_UShortBuffer_size arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_UShortBuffer_capacity arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_UShortBuffer_reserve arginfo_class_GL_Buffer_BufferInterface_reserve

#define arginfo_class_GL_Buffer_UShortBuffer_dump arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_ByteBuffer___construct arginfo_class_GL_Buffer_BufferInterface___construct

#define arginfo_class_GL_Buffer_ByteBuffer___toString arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_ByteBuffer_push arginfo_class_GL_Buffer_HFloatBuffer_push

#define arginfo_class_GL_Buffer_ByteBuffer_pushArray arginfo_class_GL_Buffer_FloatBuffer_pushArray

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_ByteBuffer_append, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\ByteBuffer, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_ByteBuffer_fill arginfo_class_GL_Buffer_HFloatBuffer_fill

#define arginfo_class_GL_Buffer_ByteBuffer_clear arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Buffer_ByteBuffer_size arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_ByteBuffer_capacity arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_ByteBuffer_reserve arginfo_class_GL_Buffer_BufferInterface_reserve

#define arginfo_class_GL_Buffer_ByteBuffer_dump arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_UByteBuffer___construct arginfo_class_GL_Buffer_BufferInterface___construct

#define arginfo_class_GL_Buffer_UByteBuffer___toString arginfo_class_GL_Math_Vec2___toString

#define arginfo_class_GL_Buffer_UByteBuffer_push arginfo_class_GL_Buffer_HFloatBuffer_push

#define arginfo_class_GL_Buffer_UByteBuffer_pushArray arginfo_class_GL_Buffer_FloatBuffer_pushArray

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_UByteBuffer_append, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\\125ByteBuffer, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Buffer_UByteBuffer_pushString, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, str, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Buffer_UByteBuffer_fill arginfo_class_GL_Buffer_HFloatBuffer_fill

#define arginfo_class_GL_Buffer_UByteBuffer_clear arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Buffer_UByteBuffer_size arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_UByteBuffer_capacity arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Buffer_UByteBuffer_reserve arginfo_class_GL_Buffer_BufferInterface_reserve

#define arginfo_class_GL_Buffer_UByteBuffer_dump arginfo_class_GL_Math_Vec2___toString

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor_rgb, 0, 3, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_TYPE_INFO(0, r, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, g, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, b, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor_rgba, 0, 4, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_TYPE_INFO(0, r, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, g, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, b, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, a, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor_hsl, 0, 3, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_TYPE_INFO(0, h, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, s, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, l, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor_hsla, 0, 4, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_TYPE_INFO(0, h, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, s, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, l, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, a, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor_irgb, 0, 3, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_TYPE_INFO(0, r, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, g, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, b, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor_irgba, 0, 4, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_TYPE_INFO(0, r, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, g, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, b, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, a, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor_hex, 0, 1, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_TYPE_INFO(0, hex, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor_red, 0, 0, GL\\VectorGraphics\\VGColor, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGColor_green arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_blue arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_white arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_black arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_transparent arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_yellow arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_cyan arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_magenta arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_orange arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_pink arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_purple arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_brown arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_gray arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_darkGray arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_lightGray arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_random arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_randomGray arginfo_class_GL_VectorGraphics_VGColor_red

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor___construct, 0, 0, 4)
	ZEND_ARG_TYPE_INFO(0, r, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, g, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, b, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, a, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGColor_getHSLA arginfo_class_GL_Math_Vec4_copy

#define arginfo_class_GL_VectorGraphics_VGColor_getHSL arginfo_class_GL_Math_Vec3_copy

#define arginfo_class_GL_VectorGraphics_VGColor_getVec4 arginfo_class_GL_Math_Vec4_copy

#define arginfo_class_GL_VectorGraphics_VGColor_getVec3 arginfo_class_GL_Math_Vec3_copy

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor_darken, 0, 1, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_TYPE_INFO(0, amount, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGColor_lighten arginfo_class_GL_VectorGraphics_VGColor_darken

#define arginfo_class_GL_VectorGraphics_VGColor_invert arginfo_class_GL_VectorGraphics_VGColor_red

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGColor_withAlpha, 0, 1, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_TYPE_INFO(0, alpha, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGColor_copy arginfo_class_GL_VectorGraphics_VGColor_red

#define arginfo_class_GL_VectorGraphics_VGColor_contrast arginfo_class_GL_VectorGraphics_VGColor_red

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGImage_makePaint, 0, 4, GL\\VectorGraphics\\VGPaint, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, w, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, h, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, angle, IS_DOUBLE, 0, "0.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, alpha, IS_DOUBLE, 0, "1.0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGImage_makePaintCentered, 0, 4, GL\\VectorGraphics\\VGPaint, 0)
	ZEND_ARG_TYPE_INFO(0, cx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, cy, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, w, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, h, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, angle, IS_DOUBLE, 0, "0.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, alpha, IS_DOUBLE, 0, "1.0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_SVGImage_fromDisk, 0, 1, GL\\VectorGraphics\\SVGImage, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_SVGImage_fromString, 0, 1, GL\\VectorGraphics\\SVGImage, 0)
	ZEND_ARG_TYPE_INFO(0, svg, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext___construct, 0, 0, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, flags, IS_LONG, 0, "0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_fillColori, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, r, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, g, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, b, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, a, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_strokeColori arginfo_class_GL_VectorGraphics_VGContext_fillColori

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_fillColorVec4, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, vec, GL\\Math\\Vec4, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_strokeColorVec4 arginfo_class_GL_VectorGraphics_VGContext_fillColorVec4

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_transformPoint, 0, 2, GL\\Math\\Vec2, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_transformVec2 arginfo_class_GL_Math_Vec2_normalized

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_imageFromTexture, 0, 1, GL\\VectorGraphics\\VGImage, 0)
	ZEND_ARG_OBJ_INFO(0, texture, GL\\Texture\\Texture2D, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, repeatMode, IS_LONG, 0, "GL\\VectorGraphics\\VGImage::REPEAT_NONE")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, filterMode, IS_LONG, 0, "GL\\VectorGraphics\\VGImage::FILTER_LINEAR")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_imageFromHandle, 0, 3, GL\\VectorGraphics\\VGImage, 0)
	ZEND_ARG_TYPE_INFO(0, handle, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, repeatMode, IS_LONG, 0, "GL\\VectorGraphics\\VGImage::REPEAT_NONE")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, filterMode, IS_LONG, 0, "GL\\VectorGraphics\\VGImage::FILTER_LINEAR")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_drawSVG, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, svg, GL\\VectorGraphics\\SVGImage, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, x, IS_DOUBLE, 0, "0.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, y, IS_DOUBLE, 0, "0.0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, w, IS_DOUBLE, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, h, IS_DOUBLE, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_linearGradient, 0, 6, GL\\VectorGraphics\\VGPaint, 0)
	ZEND_ARG_TYPE_INFO(0, sx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, sy, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, ex, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, ey, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, icol, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_OBJ_INFO(0, ocol, GL\\VectorGraphics\\VGColor, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_boxGradient, 0, 8, GL\\VectorGraphics\\VGPaint, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, w, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, h, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, r, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, f, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, icol, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_OBJ_INFO(0, ocol, GL\\VectorGraphics\\VGColor, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_radialGradient, 0, 6, GL\\VectorGraphics\\VGPaint, 0)
	ZEND_ARG_TYPE_INFO(0, cx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, cy, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, inr, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, outr, IS_DOUBLE, 0)
	ZEND_ARG_OBJ_INFO(0, icol, GL\\VectorGraphics\\VGColor, 0)
	ZEND_ARG_OBJ_INFO(0, ocol, GL\\VectorGraphics\\VGColor, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_beginFrame, 0, 3, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, windowWidth, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, windowHeight, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, devicePixelRatio, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_cancelFrame arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_endFrame arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_globalCompositeOperation, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, op, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_globalCompositeBlendFunc, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, sfactor, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, dfactor, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_globalCompositeBlendFuncSeparate, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, srcRGB, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, dstRGB, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, srcAlpha, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, dstAlpha, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_save arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_restore arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_reset arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_shapeAntiAlias, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, enabled, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_strokeColor, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, color, GL\\VectorGraphics\\VGColor, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_strokePaint, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, paint, GL\\VectorGraphics\\VGPaint, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_fillColor arginfo_class_GL_VectorGraphics_VGContext_strokeColor

#define arginfo_class_GL_VectorGraphics_VGContext_fillPaint arginfo_class_GL_VectorGraphics_VGContext_strokePaint

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_miterLimit, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, limit, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_strokeWidth, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, size, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_lineCap, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, cap, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_lineJoin, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, join, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_globalAlpha, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, alpha, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_resetTransform arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_transform, 0, 6, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, a, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, b, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, c, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, d, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, e, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, f, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_translate, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_rotate, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, angle, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_skewX arginfo_class_GL_VectorGraphics_VGContext_rotate

#define arginfo_class_GL_VectorGraphics_VGContext_skewY arginfo_class_GL_VectorGraphics_VGContext_rotate

#define arginfo_class_GL_VectorGraphics_VGContext_scale arginfo_class_GL_VectorGraphics_VGContext_translate

#define arginfo_class_GL_VectorGraphics_VGContext_currentTransform arginfo_class_GL_Buffer_FloatBuffer_append

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_transformPointCurrent, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(1, dstx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(1, dsty, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, srcx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, srcy, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_imageSize, 0, 3, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, image, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(1, w, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(1, h, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_deleteImage, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, image, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_scissor, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, w, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, h, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_intersectScissor arginfo_class_GL_VectorGraphics_VGContext_scissor

#define arginfo_class_GL_VectorGraphics_VGContext_resetScissor arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_beginPath arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_moveTo arginfo_class_GL_VectorGraphics_VGContext_translate

#define arginfo_class_GL_VectorGraphics_VGContext_lineTo arginfo_class_GL_VectorGraphics_VGContext_translate

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_bezierTo, 0, 6, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, c1x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, c1y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, c2x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, c2y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_quadTo, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, cx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, cy, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_arcTo, 0, 5, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x1, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y1, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x2, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y2, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radius, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_closePath arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_pathWinding, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, dir, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_arc, 0, 6, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, cx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, cy, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, r, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, a0, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, a1, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, dir, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_rect arginfo_class_GL_VectorGraphics_VGContext_scissor

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_roundedRect, 0, 5, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, w, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, h, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, r, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_roundedRectVarying, 0, 8, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, w, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, h, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radTopLeft, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radTopRight, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radBottomRight, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radBottomLeft, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_ellipse, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, cx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, cy, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, rx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, ry, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_circle, 0, 3, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, cx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, cy, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, r, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_fill arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_fillEvenOdd arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_stroke arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_clip arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_resetClip arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_saveClip arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_restoreClip arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_createFont, 0, 2, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, filename, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_createFontAtIndex, 0, 3, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, filename, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, fontIndex, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_findFont, 0, 1, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_addFallbackFontId, 0, 2, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, baseFont, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, fallbackFont, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_addFallbackFont, 0, 2, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, baseFont, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, fallbackFont, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_resetFallbackFontsId, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, baseFont, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_resetFallbackFonts, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, baseFont, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_fontSize arginfo_class_GL_VectorGraphics_VGContext_strokeWidth

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_fontBlur, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, blur, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_textLetterSpacing, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, spacing, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_textLineHeight, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, lineHeight, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_textAlign, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, align, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_fontFaceId, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, font, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_fontFace, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, font, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_text, 0, 3, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, string, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_textBox, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, breakRowWidth, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, string, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_textBounds, 0, 3, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, string, IS_STRING, 0)
	ZEND_ARG_OBJ_INFO_WITH_DEFAULT_VALUE(1, bounds, GL\\Math\\Vec4, 1, "NULL")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_textBoxBounds, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, breakRowWidth, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, string, IS_STRING, 0)
	ZEND_ARG_OBJ_INFO_WITH_DEFAULT_VALUE(1, bounds, GL\\Math\\Vec4, 1, "NULL")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_VectorGraphics_VGContext_textMetrics, 0, 3, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(1, ascender, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(1, descender, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(1, lineh, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_VectorGraphics_VGContext_deleteInternal arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_VectorGraphics_VGContext_debugDumpPathCache arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_GL_Audio_Engine___construct, 0, 0, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, options, IS_ARRAY, 0, "[]")
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Audio_Engine_start arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Audio_Engine_stop arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(arginfo_class_GL_Audio_Engine_soundFromDisk, 0, 1, GL\\Audio\\Sound, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Engine_setMasterVolume, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, volume, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Audio_Engine_getMasterVolume arginfo_class_GL_Math_Vec2_length

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Engine_setListenerPosition, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, position, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Engine_setListenerDirection, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, direction, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Engine_setListenerWorldUp, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, worldUp, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Audio_Sound___construct arginfo_class_GL_Math_Mat4___construct

#define arginfo_class_GL_Audio_Sound_getSampleRate arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Audio_Sound_getChannels arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Audio_Sound_getLength arginfo_class_GL_Math_Vec2_length

#define arginfo_class_GL_Audio_Sound_getLengthPCM arginfo_class_GL_Texture_Texture2D_width

#define arginfo_class_GL_Audio_Sound_play arginfo_class_GL_Math_Vec2_normalize

#define arginfo_class_GL_Audio_Sound_stop arginfo_class_GL_Math_Vec2_normalize

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_setStartMs, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, start, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_setStopMs, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, stop, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Audio_Sound_isPlaying arginfo_class_GL_Texture_Texture2D_isHDR

#define arginfo_class_GL_Audio_Sound_getCursor arginfo_class_GL_Math_Vec2_length

#define arginfo_class_GL_Audio_Sound_getCursorPCM arginfo_class_GL_Texture_Texture2D_width

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_seekTo, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, cursor, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_seekToPCM, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, cursor, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Audio_Sound_setVolume arginfo_class_GL_Audio_Engine_setMasterVolume

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_setPitch, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, pitch, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_setLoop, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, loop, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Audio_Sound_setPosition arginfo_class_GL_Audio_Engine_setListenerPosition

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_setMinDistance, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, distance, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Audio_Sound_getMinDistance arginfo_class_GL_Math_Vec2_length

#define arginfo_class_GL_Audio_Sound_setMaxDistance arginfo_class_GL_Audio_Sound_setMinDistance

#define arginfo_class_GL_Audio_Sound_getMaxDistance arginfo_class_GL_Math_Vec2_length

#define arginfo_class_GL_Audio_Sound_setDirection arginfo_class_GL_Audio_Engine_setListenerDirection

#define arginfo_class_GL_Audio_Sound_getDirection arginfo_class_GL_Math_Vec3_copy

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_setVelocity, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, velocity, GL\\Math\\Vec3, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Audio_Sound_getVelocity arginfo_class_GL_Math_Vec3_copy

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_fadeIn, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, duration, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Audio_Sound_fadeOut arginfo_class_GL_Audio_Sound_fadeIn

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_setFade, 0, 3, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, fromVolume, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, toVolume, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, duration, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_GL_Audio_Sound_getCurrentFadeVolume arginfo_class_GL_Math_Vec2_length

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_GL_Audio_Sound_readFrames, 0, 2, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, frames, IS_LONG, 0)
	ZEND_ARG_OBJ_INFO(0, buffer, GL\\Buffer\\BufferInterface, 0)
ZEND_END_ARG_INFO()

ZEND_METHOD(GL_Texture_Texture2D, fromDisk);
ZEND_METHOD(GL_Texture_Texture2D, fromBuffer);
ZEND_METHOD(GL_Texture_Texture2D, fromBufferHDR);
ZEND_METHOD(GL_Texture_Texture2D, buffer);
ZEND_METHOD(GL_Texture_Texture2D, width);
ZEND_METHOD(GL_Texture_Texture2D, height);
ZEND_METHOD(GL_Texture_Texture2D, channels);
ZEND_METHOD(GL_Texture_Texture2D, isHDR);
ZEND_METHOD(GL_Texture_Texture2D, writeJPG);
ZEND_METHOD(GL_Texture_Texture2D, writePNG);
ZEND_METHOD(GL_Texture_Texture2D, writeBMP);
ZEND_METHOD(GL_Texture_Texture2D, writeTGA);
ZEND_METHOD(GL_Texture_Texture2D, writeHDR);
ZEND_METHOD(GL_Noise, perlin);
ZEND_METHOD(GL_Noise, ridge);
ZEND_METHOD(GL_Noise, fbm);
ZEND_METHOD(GL_Noise, turbulence);
ZEND_METHOD(GL_Noise, perlinFill2D);
ZEND_METHOD(GL_Noise, ridgeFill2D);
ZEND_METHOD(GL_Noise, fbmFill2D);
ZEND_METHOD(GL_Noise, turbulenceFill2D);
ZEND_METHOD(GL_Noise, islandFill2D);
ZEND_METHOD(GL_Math_GLM, radians);
ZEND_METHOD(GL_Math_GLM, angle);
ZEND_METHOD(GL_Math_GLM, triangleNormal);
ZEND_METHOD(GL_Math_GLM, normalize);
ZEND_METHOD(GL_Math_Vec2, __construct);
ZEND_METHOD(GL_Math_Vec2, copy);
ZEND_METHOD(GL_Math_Vec2, normalized);
ZEND_METHOD(GL_Math_Vec2, distance);
ZEND_METHOD(GL_Math_Vec2, distance2);
ZEND_METHOD(GL_Math_Vec2, dot);
ZEND_METHOD(GL_Math_Vec2, mix);
ZEND_METHOD(GL_Math_Vec2, lerp);
ZEND_METHOD(GL_Math_Vec2, slerp);
ZEND_METHOD(GL_Math_Vec2, length);
ZEND_METHOD(GL_Math_Vec2, distanceTo);
ZEND_METHOD(GL_Math_Vec2, distance2To);
ZEND_METHOD(GL_Math_Vec2, normalize);
ZEND_METHOD(GL_Math_Vec2, abs);
ZEND_METHOD(GL_Math_Vec2, __toString);
ZEND_METHOD(GL_Math_Vec3, __construct);
ZEND_METHOD(GL_Math_Vec3, copy);
ZEND_METHOD(GL_Math_Vec3, normalized);
ZEND_METHOD(GL_Math_Vec3, distance);
ZEND_METHOD(GL_Math_Vec3, distance2);
ZEND_METHOD(GL_Math_Vec3, dot);
ZEND_METHOD(GL_Math_Vec3, mix);
ZEND_METHOD(GL_Math_Vec3, lerp);
ZEND_METHOD(GL_Math_Vec3, slerp);
ZEND_METHOD(GL_Math_Vec3, length);
ZEND_METHOD(GL_Math_Vec3, distanceTo);
ZEND_METHOD(GL_Math_Vec3, distance2To);
ZEND_METHOD(GL_Math_Vec3, normalize);
ZEND_METHOD(GL_Math_Vec3, abs);
ZEND_METHOD(GL_Math_Vec3, cross);
ZEND_METHOD(GL_Math_Vec3, multiplyQuat);
ZEND_METHOD(GL_Math_Vec3, __toString);
ZEND_METHOD(GL_Math_Vec4, __construct);
ZEND_METHOD(GL_Math_Vec4, copy);
ZEND_METHOD(GL_Math_Vec4, normalized);
ZEND_METHOD(GL_Math_Vec4, distance);
ZEND_METHOD(GL_Math_Vec4, distance2);
ZEND_METHOD(GL_Math_Vec4, dot);
ZEND_METHOD(GL_Math_Vec4, mix);
ZEND_METHOD(GL_Math_Vec4, lerp);
ZEND_METHOD(GL_Math_Vec4, slerp);
ZEND_METHOD(GL_Math_Vec4, length);
ZEND_METHOD(GL_Math_Vec4, distanceTo);
ZEND_METHOD(GL_Math_Vec4, distance2To);
ZEND_METHOD(GL_Math_Vec4, normalize);
ZEND_METHOD(GL_Math_Vec4, abs);
ZEND_METHOD(GL_Math_Vec4, __toString);
ZEND_METHOD(GL_Math_Quat, __construct);
ZEND_METHOD(GL_Math_Quat, copy);
ZEND_METHOD(GL_Math_Quat, fromMat4);
ZEND_METHOD(GL_Math_Quat, fromVec4);
ZEND_METHOD(GL_Math_Quat, inverted);
ZEND_METHOD(GL_Math_Quat, normalized);
ZEND_METHOD(GL_Math_Quat, mix);
ZEND_METHOD(GL_Math_Quat, lerp);
ZEND_METHOD(GL_Math_Quat, slerp);
ZEND_METHOD(GL_Math_Quat, dot);
ZEND_METHOD(GL_Math_Quat, multiply);
ZEND_METHOD(GL_Math_Quat, multiplyVec3);
ZEND_METHOD(GL_Math_Quat, multiplyMat4);
ZEND_METHOD(GL_Math_Quat, normalize);
ZEND_METHOD(GL_Math_Quat, length);
ZEND_METHOD(GL_Math_Quat, eulerAngles);
ZEND_METHOD(GL_Math_Quat, rotate);
ZEND_METHOD(GL_Math_Quat, inverse);
ZEND_METHOD(GL_Math_Quat, mat4);
ZEND_METHOD(GL_Math_Quat, __toString);
ZEND_METHOD(GL_Math_Mat4, __construct);
ZEND_METHOD(GL_Math_Mat4, copy);
ZEND_METHOD(GL_Math_Mat4, fromArray);
ZEND_METHOD(GL_Math_Mat4, inverted);
ZEND_METHOD(GL_Math_Mat4, multiplyQuat);
ZEND_METHOD(GL_Math_Mat4, row);
ZEND_METHOD(GL_Math_Mat4, setRow);
ZEND_METHOD(GL_Math_Mat4, col);
ZEND_METHOD(GL_Math_Mat4, setCol);
ZEND_METHOD(GL_Math_Mat4, lookAt);
ZEND_METHOD(GL_Math_Mat4, perspective);
ZEND_METHOD(GL_Math_Mat4, ortho);
ZEND_METHOD(GL_Math_Mat4, transpose);
ZEND_METHOD(GL_Math_Mat4, inverse);
ZEND_METHOD(GL_Math_Mat4, scale);
ZEND_METHOD(GL_Math_Mat4, translate);
ZEND_METHOD(GL_Math_Mat4, rotate);
ZEND_METHOD(GL_Math_Mat4, determinant);
ZEND_METHOD(GL_Math_Mat4, __toString);
ZEND_METHOD(GL_Buffer_FloatBuffer, __construct);
ZEND_METHOD(GL_Buffer_FloatBuffer, __toString);
ZEND_METHOD(GL_Buffer_FloatBuffer, push);
ZEND_METHOD(GL_Buffer_FloatBuffer, pushArray);
ZEND_METHOD(GL_Buffer_FloatBuffer, append);
ZEND_METHOD(GL_Buffer_FloatBuffer, pushVec2);
ZEND_METHOD(GL_Buffer_FloatBuffer, pushVec3);
ZEND_METHOD(GL_Buffer_FloatBuffer, pushVec4);
ZEND_METHOD(GL_Buffer_FloatBuffer, pushMat4);
ZEND_METHOD(GL_Buffer_FloatBuffer, quantizeToUChar);
ZEND_METHOD(GL_Buffer_FloatBuffer, fill);
ZEND_METHOD(GL_Buffer_FloatBuffer, clear);
ZEND_METHOD(GL_Buffer_FloatBuffer, size);
ZEND_METHOD(GL_Buffer_FloatBuffer, capacity);
ZEND_METHOD(GL_Buffer_FloatBuffer, reserve);
ZEND_METHOD(GL_Buffer_FloatBuffer, dump);
ZEND_METHOD(GL_Buffer_HFloatBuffer, __construct);
ZEND_METHOD(GL_Buffer_HFloatBuffer, __toString);
ZEND_METHOD(GL_Buffer_HFloatBuffer, push);
ZEND_METHOD(GL_Buffer_HFloatBuffer, pushArray);
ZEND_METHOD(GL_Buffer_HFloatBuffer, append);
ZEND_METHOD(GL_Buffer_HFloatBuffer, fill);
ZEND_METHOD(GL_Buffer_HFloatBuffer, clear);
ZEND_METHOD(GL_Buffer_HFloatBuffer, size);
ZEND_METHOD(GL_Buffer_HFloatBuffer, capacity);
ZEND_METHOD(GL_Buffer_HFloatBuffer, reserve);
ZEND_METHOD(GL_Buffer_HFloatBuffer, dump);
ZEND_METHOD(GL_Buffer_DoubleBuffer, __construct);
ZEND_METHOD(GL_Buffer_DoubleBuffer, __toString);
ZEND_METHOD(GL_Buffer_DoubleBuffer, push);
ZEND_METHOD(GL_Buffer_DoubleBuffer, pushArray);
ZEND_METHOD(GL_Buffer_DoubleBuffer, append);
ZEND_METHOD(GL_Buffer_DoubleBuffer, fill);
ZEND_METHOD(GL_Buffer_DoubleBuffer, clear);
ZEND_METHOD(GL_Buffer_DoubleBuffer, size);
ZEND_METHOD(GL_Buffer_DoubleBuffer, capacity);
ZEND_METHOD(GL_Buffer_DoubleBuffer, reserve);
ZEND_METHOD(GL_Buffer_DoubleBuffer, dump);
ZEND_METHOD(GL_Buffer_IntBuffer, __construct);
ZEND_METHOD(GL_Buffer_IntBuffer, __toString);
ZEND_METHOD(GL_Buffer_IntBuffer, push);
ZEND_METHOD(GL_Buffer_IntBuffer, pushArray);
ZEND_METHOD(GL_Buffer_IntBuffer, append);
ZEND_METHOD(GL_Buffer_IntBuffer, fill);
ZEND_METHOD(GL_Buffer_IntBuffer, clear);
ZEND_METHOD(GL_Buffer_IntBuffer, size);
ZEND_METHOD(GL_Buffer_IntBuffer, capacity);
ZEND_METHOD(GL_Buffer_IntBuffer, reserve);
ZEND_METHOD(GL_Buffer_IntBuffer, dump);
ZEND_METHOD(GL_Buffer_UIntBuffer, __construct);
ZEND_METHOD(GL_Buffer_UIntBuffer, __toString);
ZEND_METHOD(GL_Buffer_UIntBuffer, push);
ZEND_METHOD(GL_Buffer_UIntBuffer, pushArray);
ZEND_METHOD(GL_Buffer_UIntBuffer, append);
ZEND_METHOD(GL_Buffer_UIntBuffer, fill);
ZEND_METHOD(GL_Buffer_UIntBuffer, clear);
ZEND_METHOD(GL_Buffer_UIntBuffer, size);
ZEND_METHOD(GL_Buffer_UIntBuffer, capacity);
ZEND_METHOD(GL_Buffer_UIntBuffer, reserve);
ZEND_METHOD(GL_Buffer_UIntBuffer, dump);
ZEND_METHOD(GL_Buffer_ShortBuffer, __construct);
ZEND_METHOD(GL_Buffer_ShortBuffer, __toString);
ZEND_METHOD(GL_Buffer_ShortBuffer, push);
ZEND_METHOD(GL_Buffer_ShortBuffer, pushArray);
ZEND_METHOD(GL_Buffer_ShortBuffer, append);
ZEND_METHOD(GL_Buffer_ShortBuffer, fill);
ZEND_METHOD(GL_Buffer_ShortBuffer, clear);
ZEND_METHOD(GL_Buffer_ShortBuffer, size);
ZEND_METHOD(GL_Buffer_ShortBuffer, capacity);
ZEND_METHOD(GL_Buffer_ShortBuffer, reserve);
ZEND_METHOD(GL_Buffer_ShortBuffer, dump);
ZEND_METHOD(GL_Buffer_UShortBuffer, __construct);
ZEND_METHOD(GL_Buffer_UShortBuffer, __toString);
ZEND_METHOD(GL_Buffer_UShortBuffer, push);
ZEND_METHOD(GL_Buffer_UShortBuffer, pushArray);
ZEND_METHOD(GL_Buffer_UShortBuffer, append);
ZEND_METHOD(GL_Buffer_UShortBuffer, fill);
ZEND_METHOD(GL_Buffer_UShortBuffer, clear);
ZEND_METHOD(GL_Buffer_UShortBuffer, size);
ZEND_METHOD(GL_Buffer_UShortBuffer, capacity);
ZEND_METHOD(GL_Buffer_UShortBuffer, reserve);
ZEND_METHOD(GL_Buffer_UShortBuffer, dump);
ZEND_METHOD(GL_Buffer_ByteBuffer, __construct);
ZEND_METHOD(GL_Buffer_ByteBuffer, __toString);
ZEND_METHOD(GL_Buffer_ByteBuffer, push);
ZEND_METHOD(GL_Buffer_ByteBuffer, pushArray);
ZEND_METHOD(GL_Buffer_ByteBuffer, append);
ZEND_METHOD(GL_Buffer_ByteBuffer, fill);
ZEND_METHOD(GL_Buffer_ByteBuffer, clear);
ZEND_METHOD(GL_Buffer_ByteBuffer, size);
ZEND_METHOD(GL_Buffer_ByteBuffer, capacity);
ZEND_METHOD(GL_Buffer_ByteBuffer, reserve);
ZEND_METHOD(GL_Buffer_ByteBuffer, dump);
ZEND_METHOD(GL_Buffer_UByteBuffer, __construct);
ZEND_METHOD(GL_Buffer_UByteBuffer, __toString);
ZEND_METHOD(GL_Buffer_UByteBuffer, push);
ZEND_METHOD(GL_Buffer_UByteBuffer, pushArray);
ZEND_METHOD(GL_Buffer_UByteBuffer, append);
ZEND_METHOD(GL_Buffer_UByteBuffer, pushString);
ZEND_METHOD(GL_Buffer_UByteBuffer, fill);
ZEND_METHOD(GL_Buffer_UByteBuffer, clear);
ZEND_METHOD(GL_Buffer_UByteBuffer, size);
ZEND_METHOD(GL_Buffer_UByteBuffer, capacity);
ZEND_METHOD(GL_Buffer_UByteBuffer, reserve);
ZEND_METHOD(GL_Buffer_UByteBuffer, dump);
ZEND_METHOD(GL_VectorGraphics_VGColor, rgb);
ZEND_METHOD(GL_VectorGraphics_VGColor, rgba);
ZEND_METHOD(GL_VectorGraphics_VGColor, hsl);
ZEND_METHOD(GL_VectorGraphics_VGColor, hsla);
ZEND_METHOD(GL_VectorGraphics_VGColor, irgb);
ZEND_METHOD(GL_VectorGraphics_VGColor, irgba);
ZEND_METHOD(GL_VectorGraphics_VGColor, hex);
ZEND_METHOD(GL_VectorGraphics_VGColor, red);
ZEND_METHOD(GL_VectorGraphics_VGColor, green);
ZEND_METHOD(GL_VectorGraphics_VGColor, blue);
ZEND_METHOD(GL_VectorGraphics_VGColor, white);
ZEND_METHOD(GL_VectorGraphics_VGColor, black);
ZEND_METHOD(GL_VectorGraphics_VGColor, transparent);
ZEND_METHOD(GL_VectorGraphics_VGColor, yellow);
ZEND_METHOD(GL_VectorGraphics_VGColor, cyan);
ZEND_METHOD(GL_VectorGraphics_VGColor, magenta);
ZEND_METHOD(GL_VectorGraphics_VGColor, orange);
ZEND_METHOD(GL_VectorGraphics_VGColor, pink);
ZEND_METHOD(GL_VectorGraphics_VGColor, purple);
ZEND_METHOD(GL_VectorGraphics_VGColor, brown);
ZEND_METHOD(GL_VectorGraphics_VGColor, gray);
ZEND_METHOD(GL_VectorGraphics_VGColor, darkGray);
ZEND_METHOD(GL_VectorGraphics_VGColor, lightGray);
ZEND_METHOD(GL_VectorGraphics_VGColor, random);
ZEND_METHOD(GL_VectorGraphics_VGColor, randomGray);
ZEND_METHOD(GL_VectorGraphics_VGColor, __construct);
ZEND_METHOD(GL_VectorGraphics_VGColor, getHSLA);
ZEND_METHOD(GL_VectorGraphics_VGColor, getHSL);
ZEND_METHOD(GL_VectorGraphics_VGColor, getVec4);
ZEND_METHOD(GL_VectorGraphics_VGColor, getVec3);
ZEND_METHOD(GL_VectorGraphics_VGColor, darken);
ZEND_METHOD(GL_VectorGraphics_VGColor, lighten);
ZEND_METHOD(GL_VectorGraphics_VGColor, invert);
ZEND_METHOD(GL_VectorGraphics_VGColor, withAlpha);
ZEND_METHOD(GL_VectorGraphics_VGColor, copy);
ZEND_METHOD(GL_VectorGraphics_VGColor, contrast);
ZEND_METHOD(GL_VectorGraphics_VGImage, makePaint);
ZEND_METHOD(GL_VectorGraphics_VGImage, makePaintCentered);
ZEND_METHOD(GL_VectorGraphics_SVGImage, fromDisk);
ZEND_METHOD(GL_VectorGraphics_SVGImage, fromString);
ZEND_METHOD(GL_VectorGraphics_VGContext, __construct);
ZEND_METHOD(GL_VectorGraphics_VGContext, fillColori);
ZEND_METHOD(GL_VectorGraphics_VGContext, strokeColori);
ZEND_METHOD(GL_VectorGraphics_VGContext, fillColorVec4);
ZEND_METHOD(GL_VectorGraphics_VGContext, strokeColorVec4);
ZEND_METHOD(GL_VectorGraphics_VGContext, transformPoint);
ZEND_METHOD(GL_VectorGraphics_VGContext, transformVec2);
ZEND_METHOD(GL_VectorGraphics_VGContext, imageFromTexture);
ZEND_METHOD(GL_VectorGraphics_VGContext, imageFromHandle);
ZEND_METHOD(GL_VectorGraphics_VGContext, drawSVG);
ZEND_METHOD(GL_VectorGraphics_VGContext, linearGradient);
ZEND_METHOD(GL_VectorGraphics_VGContext, boxGradient);
ZEND_METHOD(GL_VectorGraphics_VGContext, radialGradient);
ZEND_METHOD(GL_VectorGraphics_VGContext, beginFrame);
ZEND_METHOD(GL_VectorGraphics_VGContext, cancelFrame);
ZEND_METHOD(GL_VectorGraphics_VGContext, endFrame);
ZEND_METHOD(GL_VectorGraphics_VGContext, globalCompositeOperation);
ZEND_METHOD(GL_VectorGraphics_VGContext, globalCompositeBlendFunc);
ZEND_METHOD(GL_VectorGraphics_VGContext, globalCompositeBlendFuncSeparate);
ZEND_METHOD(GL_VectorGraphics_VGContext, save);
ZEND_METHOD(GL_VectorGraphics_VGContext, restore);
ZEND_METHOD(GL_VectorGraphics_VGContext, reset);
ZEND_METHOD(GL_VectorGraphics_VGContext, shapeAntiAlias);
ZEND_METHOD(GL_VectorGraphics_VGContext, strokeColor);
ZEND_METHOD(GL_VectorGraphics_VGContext, strokePaint);
ZEND_METHOD(GL_VectorGraphics_VGContext, fillColor);
ZEND_METHOD(GL_VectorGraphics_VGContext, fillPaint);
ZEND_METHOD(GL_VectorGraphics_VGContext, miterLimit);
ZEND_METHOD(GL_VectorGraphics_VGContext, strokeWidth);
ZEND_METHOD(GL_VectorGraphics_VGContext, lineCap);
ZEND_METHOD(GL_VectorGraphics_VGContext, lineJoin);
ZEND_METHOD(GL_VectorGraphics_VGContext, globalAlpha);
ZEND_METHOD(GL_VectorGraphics_VGContext, resetTransform);
ZEND_METHOD(GL_VectorGraphics_VGContext, transform);
ZEND_METHOD(GL_VectorGraphics_VGContext, translate);
ZEND_METHOD(GL_VectorGraphics_VGContext, rotate);
ZEND_METHOD(GL_VectorGraphics_VGContext, skewX);
ZEND_METHOD(GL_VectorGraphics_VGContext, skewY);
ZEND_METHOD(GL_VectorGraphics_VGContext, scale);
ZEND_METHOD(GL_VectorGraphics_VGContext, currentTransform);
ZEND_METHOD(GL_VectorGraphics_VGContext, transformPointCurrent);
ZEND_METHOD(GL_VectorGraphics_VGContext, imageSize);
ZEND_METHOD(GL_VectorGraphics_VGContext, deleteImage);
ZEND_METHOD(GL_VectorGraphics_VGContext, scissor);
ZEND_METHOD(GL_VectorGraphics_VGContext, intersectScissor);
ZEND_METHOD(GL_VectorGraphics_VGContext, resetScissor);
ZEND_METHOD(GL_VectorGraphics_VGContext, beginPath);
ZEND_METHOD(GL_VectorGraphics_VGContext, moveTo);
ZEND_METHOD(GL_VectorGraphics_VGContext, lineTo);
ZEND_METHOD(GL_VectorGraphics_VGContext, bezierTo);
ZEND_METHOD(GL_VectorGraphics_VGContext, quadTo);
ZEND_METHOD(GL_VectorGraphics_VGContext, arcTo);
ZEND_METHOD(GL_VectorGraphics_VGContext, closePath);
ZEND_METHOD(GL_VectorGraphics_VGContext, pathWinding);
ZEND_METHOD(GL_VectorGraphics_VGContext, arc);
ZEND_METHOD(GL_VectorGraphics_VGContext, rect);
ZEND_METHOD(GL_VectorGraphics_VGContext, roundedRect);
ZEND_METHOD(GL_VectorGraphics_VGContext, roundedRectVarying);
ZEND_METHOD(GL_VectorGraphics_VGContext, ellipse);
ZEND_METHOD(GL_VectorGraphics_VGContext, circle);
ZEND_METHOD(GL_VectorGraphics_VGContext, fill);
ZEND_METHOD(GL_VectorGraphics_VGContext, fillEvenOdd);
ZEND_METHOD(GL_VectorGraphics_VGContext, stroke);
ZEND_METHOD(GL_VectorGraphics_VGContext, clip);
ZEND_METHOD(GL_VectorGraphics_VGContext, resetClip);
ZEND_METHOD(GL_VectorGraphics_VGContext, saveClip);
ZEND_METHOD(GL_VectorGraphics_VGContext, restoreClip);
ZEND_METHOD(GL_VectorGraphics_VGContext, createFont);
ZEND_METHOD(GL_VectorGraphics_VGContext, createFontAtIndex);
ZEND_METHOD(GL_VectorGraphics_VGContext, findFont);
ZEND_METHOD(GL_VectorGraphics_VGContext, addFallbackFontId);
ZEND_METHOD(GL_VectorGraphics_VGContext, addFallbackFont);
ZEND_METHOD(GL_VectorGraphics_VGContext, resetFallbackFontsId);
ZEND_METHOD(GL_VectorGraphics_VGContext, resetFallbackFonts);
ZEND_METHOD(GL_VectorGraphics_VGContext, fontSize);
ZEND_METHOD(GL_VectorGraphics_VGContext, fontBlur);
ZEND_METHOD(GL_VectorGraphics_VGContext, textLetterSpacing);
ZEND_METHOD(GL_VectorGraphics_VGContext, textLineHeight);
ZEND_METHOD(GL_VectorGraphics_VGContext, textAlign);
ZEND_METHOD(GL_VectorGraphics_VGContext, fontFaceId);
ZEND_METHOD(GL_VectorGraphics_VGContext, fontFace);
ZEND_METHOD(GL_VectorGraphics_VGContext, text);
ZEND_METHOD(GL_VectorGraphics_VGContext, textBox);
ZEND_METHOD(GL_VectorGraphics_VGContext, textBounds);
ZEND_METHOD(GL_VectorGraphics_VGContext, textBoxBounds);
ZEND_METHOD(GL_VectorGraphics_VGContext, textMetrics);
ZEND_METHOD(GL_VectorGraphics_VGContext, deleteInternal);
ZEND_METHOD(GL_VectorGraphics_VGContext, debugDumpPathCache);
ZEND_METHOD(GL_Audio_Engine, __construct);
ZEND_METHOD(GL_Audio_Engine, start);
ZEND_METHOD(GL_Audio_Engine, stop);
ZEND_METHOD(GL_Audio_Engine, soundFromDisk);
ZEND_METHOD(GL_Audio_Engine, setMasterVolume);
ZEND_METHOD(GL_Audio_Engine, getMasterVolume);
ZEND_METHOD(GL_Audio_Engine, setListenerPosition);
ZEND_METHOD(GL_Audio_Engine, setListenerDirection);
ZEND_METHOD(GL_Audio_Engine, setListenerWorldUp);
ZEND_METHOD(GL_Audio_Sound, __construct);
ZEND_METHOD(GL_Audio_Sound, getSampleRate);
ZEND_METHOD(GL_Audio_Sound, getChannels);
ZEND_METHOD(GL_Audio_Sound, getLength);
ZEND_METHOD(GL_Audio_Sound, getLengthPCM);
ZEND_METHOD(GL_Audio_Sound, play);
ZEND_METHOD(GL_Audio_Sound, stop);
ZEND_METHOD(GL_Audio_Sound, setStartMs);
ZEND_METHOD(GL_Audio_Sound, setStopMs);
ZEND_METHOD(GL_Audio_Sound, isPlaying);
ZEND_METHOD(GL_Audio_Sound, getCursor);
ZEND_METHOD(GL_Audio_Sound, getCursorPCM);
ZEND_METHOD(GL_Audio_Sound, seekTo);
ZEND_METHOD(GL_Audio_Sound, seekToPCM);
ZEND_METHOD(GL_Audio_Sound, setVolume);
ZEND_METHOD(GL_Audio_Sound, setPitch);
ZEND_METHOD(GL_Audio_Sound, setLoop);
ZEND_METHOD(GL_Audio_Sound, setPosition);
ZEND_METHOD(GL_Audio_Sound, setMinDistance);
ZEND_METHOD(GL_Audio_Sound, getMinDistance);
ZEND_METHOD(GL_Audio_Sound, setMaxDistance);
ZEND_METHOD(GL_Audio_Sound, getMaxDistance);
ZEND_METHOD(GL_Audio_Sound, setDirection);
ZEND_METHOD(GL_Audio_Sound, getDirection);
ZEND_METHOD(GL_Audio_Sound, setVelocity);
ZEND_METHOD(GL_Audio_Sound, getVelocity);
ZEND_METHOD(GL_Audio_Sound, fadeIn);
ZEND_METHOD(GL_Audio_Sound, fadeOut);
ZEND_METHOD(GL_Audio_Sound, setFade);
ZEND_METHOD(GL_Audio_Sound, getCurrentFadeVolume);
ZEND_METHOD(GL_Audio_Sound, readFrames);

static const zend_function_entry class_GL_Texture_Texture2D_methods[] = {
	ZEND_ME(GL_Texture_Texture2D, fromDisk, arginfo_class_GL_Texture_Texture2D_fromDisk, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Texture_Texture2D, fromBuffer, arginfo_class_GL_Texture_Texture2D_fromBuffer, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Texture_Texture2D, fromBufferHDR, arginfo_class_GL_Texture_Texture2D_fromBufferHDR, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Texture_Texture2D, buffer, arginfo_class_GL_Texture_Texture2D_buffer, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Texture_Texture2D, width, arginfo_class_GL_Texture_Texture2D_width, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Texture_Texture2D, height, arginfo_class_GL_Texture_Texture2D_height, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Texture_Texture2D, channels, arginfo_class_GL_Texture_Texture2D_channels, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Texture_Texture2D, isHDR, arginfo_class_GL_Texture_Texture2D_isHDR, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Texture_Texture2D, writeJPG, arginfo_class_GL_Texture_Texture2D_writeJPG, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Texture_Texture2D, writePNG, arginfo_class_GL_Texture_Texture2D_writePNG, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Texture_Texture2D, writeBMP, arginfo_class_GL_Texture_Texture2D_writeBMP, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Texture_Texture2D, writeTGA, arginfo_class_GL_Texture_Texture2D_writeTGA, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Texture_Texture2D, writeHDR, arginfo_class_GL_Texture_Texture2D_writeHDR, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Noise_methods[] = {
	ZEND_ME(GL_Noise, perlin, arginfo_class_GL_Noise_perlin, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Noise, ridge, arginfo_class_GL_Noise_ridge, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Noise, fbm, arginfo_class_GL_Noise_fbm, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Noise, turbulence, arginfo_class_GL_Noise_turbulence, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Noise, perlinFill2D, arginfo_class_GL_Noise_perlinFill2D, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Noise, ridgeFill2D, arginfo_class_GL_Noise_ridgeFill2D, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Noise, fbmFill2D, arginfo_class_GL_Noise_fbmFill2D, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Noise, turbulenceFill2D, arginfo_class_GL_Noise_turbulenceFill2D, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Noise, islandFill2D, arginfo_class_GL_Noise_islandFill2D, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Math_GLM_methods[] = {
	ZEND_ME(GL_Math_GLM, radians, arginfo_class_GL_Math_GLM_radians, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_GLM, angle, arginfo_class_GL_Math_GLM_angle, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_GLM, triangleNormal, arginfo_class_GL_Math_GLM_triangleNormal, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_GLM, normalize, arginfo_class_GL_Math_GLM_normalize, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Math_Vec2_methods[] = {
	ZEND_ME(GL_Math_Vec2, __construct, arginfo_class_GL_Math_Vec2___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec2, copy, arginfo_class_GL_Math_Vec2_copy, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec2, normalized, arginfo_class_GL_Math_Vec2_normalized, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec2, distance, arginfo_class_GL_Math_Vec2_distance, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec2, distance2, arginfo_class_GL_Math_Vec2_distance2, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec2, dot, arginfo_class_GL_Math_Vec2_dot, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec2, mix, arginfo_class_GL_Math_Vec2_mix, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec2, lerp, arginfo_class_GL_Math_Vec2_lerp, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec2, slerp, arginfo_class_GL_Math_Vec2_slerp, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec2, length, arginfo_class_GL_Math_Vec2_length, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec2, distanceTo, arginfo_class_GL_Math_Vec2_distanceTo, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec2, distance2To, arginfo_class_GL_Math_Vec2_distance2To, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec2, normalize, arginfo_class_GL_Math_Vec2_normalize, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec2, abs, arginfo_class_GL_Math_Vec2_abs, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec2, __toString, arginfo_class_GL_Math_Vec2___toString, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Math_Vec3_methods[] = {
	ZEND_ME(GL_Math_Vec3, __construct, arginfo_class_GL_Math_Vec3___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec3, copy, arginfo_class_GL_Math_Vec3_copy, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec3, normalized, arginfo_class_GL_Math_Vec3_normalized, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec3, distance, arginfo_class_GL_Math_Vec3_distance, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec3, distance2, arginfo_class_GL_Math_Vec3_distance2, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec3, dot, arginfo_class_GL_Math_Vec3_dot, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec3, mix, arginfo_class_GL_Math_Vec3_mix, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec3, lerp, arginfo_class_GL_Math_Vec3_lerp, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec3, slerp, arginfo_class_GL_Math_Vec3_slerp, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec3, length, arginfo_class_GL_Math_Vec3_length, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec3, distanceTo, arginfo_class_GL_Math_Vec3_distanceTo, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec3, distance2To, arginfo_class_GL_Math_Vec3_distance2To, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec3, normalize, arginfo_class_GL_Math_Vec3_normalize, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec3, abs, arginfo_class_GL_Math_Vec3_abs, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec3, cross, arginfo_class_GL_Math_Vec3_cross, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec3, multiplyQuat, arginfo_class_GL_Math_Vec3_multiplyQuat, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec3, __toString, arginfo_class_GL_Math_Vec3___toString, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Math_Vec4_methods[] = {
	ZEND_ME(GL_Math_Vec4, __construct, arginfo_class_GL_Math_Vec4___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec4, copy, arginfo_class_GL_Math_Vec4_copy, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec4, normalized, arginfo_class_GL_Math_Vec4_normalized, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec4, distance, arginfo_class_GL_Math_Vec4_distance, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec4, distance2, arginfo_class_GL_Math_Vec4_distance2, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec4, dot, arginfo_class_GL_Math_Vec4_dot, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec4, mix, arginfo_class_GL_Math_Vec4_mix, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec4, lerp, arginfo_class_GL_Math_Vec4_lerp, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec4, slerp, arginfo_class_GL_Math_Vec4_slerp, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Vec4, length, arginfo_class_GL_Math_Vec4_length, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec4, distanceTo, arginfo_class_GL_Math_Vec4_distanceTo, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec4, distance2To, arginfo_class_GL_Math_Vec4_distance2To, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec4, normalize, arginfo_class_GL_Math_Vec4_normalize, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec4, abs, arginfo_class_GL_Math_Vec4_abs, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Vec4, __toString, arginfo_class_GL_Math_Vec4___toString, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Math_Quat_methods[] = {
	ZEND_ME(GL_Math_Quat, __construct, arginfo_class_GL_Math_Quat___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Quat, copy, arginfo_class_GL_Math_Quat_copy, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Quat, fromMat4, arginfo_class_GL_Math_Quat_fromMat4, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, fromVec4, arginfo_class_GL_Math_Quat_fromVec4, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, inverted, arginfo_class_GL_Math_Quat_inverted, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, normalized, arginfo_class_GL_Math_Quat_normalized, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, mix, arginfo_class_GL_Math_Quat_mix, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, lerp, arginfo_class_GL_Math_Quat_lerp, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, slerp, arginfo_class_GL_Math_Quat_slerp, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, dot, arginfo_class_GL_Math_Quat_dot, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, multiply, arginfo_class_GL_Math_Quat_multiply, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, multiplyVec3, arginfo_class_GL_Math_Quat_multiplyVec3, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, multiplyMat4, arginfo_class_GL_Math_Quat_multiplyMat4, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Quat, normalize, arginfo_class_GL_Math_Quat_normalize, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Quat, length, arginfo_class_GL_Math_Quat_length, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Quat, eulerAngles, arginfo_class_GL_Math_Quat_eulerAngles, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Quat, rotate, arginfo_class_GL_Math_Quat_rotate, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Quat, inverse, arginfo_class_GL_Math_Quat_inverse, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Quat, mat4, arginfo_class_GL_Math_Quat_mat4, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Quat, __toString, arginfo_class_GL_Math_Quat___toString, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Math_Mat4_methods[] = {
	ZEND_ME(GL_Math_Mat4, __construct, arginfo_class_GL_Math_Mat4___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, copy, arginfo_class_GL_Math_Mat4_copy, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, fromArray, arginfo_class_GL_Math_Mat4_fromArray, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Mat4, inverted, arginfo_class_GL_Math_Mat4_inverted, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Mat4, multiplyQuat, arginfo_class_GL_Math_Mat4_multiplyQuat, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_Math_Mat4, row, arginfo_class_GL_Math_Mat4_row, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, setRow, arginfo_class_GL_Math_Mat4_setRow, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, col, arginfo_class_GL_Math_Mat4_col, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, setCol, arginfo_class_GL_Math_Mat4_setCol, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, lookAt, arginfo_class_GL_Math_Mat4_lookAt, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, perspective, arginfo_class_GL_Math_Mat4_perspective, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, ortho, arginfo_class_GL_Math_Mat4_ortho, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, transpose, arginfo_class_GL_Math_Mat4_transpose, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, inverse, arginfo_class_GL_Math_Mat4_inverse, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, scale, arginfo_class_GL_Math_Mat4_scale, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, translate, arginfo_class_GL_Math_Mat4_translate, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, rotate, arginfo_class_GL_Math_Mat4_rotate, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, determinant, arginfo_class_GL_Math_Mat4_determinant, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Math_Mat4, __toString, arginfo_class_GL_Math_Mat4___toString, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Buffer_BufferInterface_methods[] = {
	ZEND_RAW_FENTRY("__construct", NULL, arginfo_class_GL_Buffer_BufferInterface___construct, ZEND_ACC_PUBLIC|ZEND_ACC_ABSTRACT, NULL, NULL)
	ZEND_RAW_FENTRY("clear", NULL, arginfo_class_GL_Buffer_BufferInterface_clear, ZEND_ACC_PUBLIC|ZEND_ACC_ABSTRACT, NULL, NULL)
	ZEND_RAW_FENTRY("size", NULL, arginfo_class_GL_Buffer_BufferInterface_size, ZEND_ACC_PUBLIC|ZEND_ACC_ABSTRACT, NULL, NULL)
	ZEND_RAW_FENTRY("capacity", NULL, arginfo_class_GL_Buffer_BufferInterface_capacity, ZEND_ACC_PUBLIC|ZEND_ACC_ABSTRACT, NULL, NULL)
	ZEND_RAW_FENTRY("reserve", NULL, arginfo_class_GL_Buffer_BufferInterface_reserve, ZEND_ACC_PUBLIC|ZEND_ACC_ABSTRACT, NULL, NULL)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Buffer_FloatBuffer_methods[] = {
	ZEND_ME(GL_Buffer_FloatBuffer, __construct, arginfo_class_GL_Buffer_FloatBuffer___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, __toString, arginfo_class_GL_Buffer_FloatBuffer___toString, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, push, arginfo_class_GL_Buffer_FloatBuffer_push, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, pushArray, arginfo_class_GL_Buffer_FloatBuffer_pushArray, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, append, arginfo_class_GL_Buffer_FloatBuffer_append, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, pushVec2, arginfo_class_GL_Buffer_FloatBuffer_pushVec2, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, pushVec3, arginfo_class_GL_Buffer_FloatBuffer_pushVec3, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, pushVec4, arginfo_class_GL_Buffer_FloatBuffer_pushVec4, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, pushMat4, arginfo_class_GL_Buffer_FloatBuffer_pushMat4, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, quantizeToUChar, arginfo_class_GL_Buffer_FloatBuffer_quantizeToUChar, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, fill, arginfo_class_GL_Buffer_FloatBuffer_fill, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, clear, arginfo_class_GL_Buffer_FloatBuffer_clear, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, size, arginfo_class_GL_Buffer_FloatBuffer_size, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, capacity, arginfo_class_GL_Buffer_FloatBuffer_capacity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, reserve, arginfo_class_GL_Buffer_FloatBuffer_reserve, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_FloatBuffer, dump, arginfo_class_GL_Buffer_FloatBuffer_dump, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Buffer_HFloatBuffer_methods[] = {
	ZEND_ME(GL_Buffer_HFloatBuffer, __construct, arginfo_class_GL_Buffer_HFloatBuffer___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_HFloatBuffer, __toString, arginfo_class_GL_Buffer_HFloatBuffer___toString, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_HFloatBuffer, push, arginfo_class_GL_Buffer_HFloatBuffer_push, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_HFloatBuffer, pushArray, arginfo_class_GL_Buffer_HFloatBuffer_pushArray, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_HFloatBuffer, append, arginfo_class_GL_Buffer_HFloatBuffer_append, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_HFloatBuffer, fill, arginfo_class_GL_Buffer_HFloatBuffer_fill, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_HFloatBuffer, clear, arginfo_class_GL_Buffer_HFloatBuffer_clear, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_HFloatBuffer, size, arginfo_class_GL_Buffer_HFloatBuffer_size, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_HFloatBuffer, capacity, arginfo_class_GL_Buffer_HFloatBuffer_capacity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_HFloatBuffer, reserve, arginfo_class_GL_Buffer_HFloatBuffer_reserve, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_HFloatBuffer, dump, arginfo_class_GL_Buffer_HFloatBuffer_dump, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Buffer_DoubleBuffer_methods[] = {
	ZEND_ME(GL_Buffer_DoubleBuffer, __construct, arginfo_class_GL_Buffer_DoubleBuffer___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_DoubleBuffer, __toString, arginfo_class_GL_Buffer_DoubleBuffer___toString, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_DoubleBuffer, push, arginfo_class_GL_Buffer_DoubleBuffer_push, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_DoubleBuffer, pushArray, arginfo_class_GL_Buffer_DoubleBuffer_pushArray, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_DoubleBuffer, append, arginfo_class_GL_Buffer_DoubleBuffer_append, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_DoubleBuffer, fill, arginfo_class_GL_Buffer_DoubleBuffer_fill, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_DoubleBuffer, clear, arginfo_class_GL_Buffer_DoubleBuffer_clear, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_DoubleBuffer, size, arginfo_class_GL_Buffer_DoubleBuffer_size, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_DoubleBuffer, capacity, arginfo_class_GL_Buffer_DoubleBuffer_capacity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_DoubleBuffer, reserve, arginfo_class_GL_Buffer_DoubleBuffer_reserve, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_DoubleBuffer, dump, arginfo_class_GL_Buffer_DoubleBuffer_dump, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Buffer_IntBuffer_methods[] = {
	ZEND_ME(GL_Buffer_IntBuffer, __construct, arginfo_class_GL_Buffer_IntBuffer___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_IntBuffer, __toString, arginfo_class_GL_Buffer_IntBuffer___toString, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_IntBuffer, push, arginfo_class_GL_Buffer_IntBuffer_push, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_IntBuffer, pushArray, arginfo_class_GL_Buffer_IntBuffer_pushArray, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_IntBuffer, append, arginfo_class_GL_Buffer_IntBuffer_append, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_IntBuffer, fill, arginfo_class_GL_Buffer_IntBuffer_fill, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_IntBuffer, clear, arginfo_class_GL_Buffer_IntBuffer_clear, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_IntBuffer, size, arginfo_class_GL_Buffer_IntBuffer_size, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_IntBuffer, capacity, arginfo_class_GL_Buffer_IntBuffer_capacity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_IntBuffer, reserve, arginfo_class_GL_Buffer_IntBuffer_reserve, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_IntBuffer, dump, arginfo_class_GL_Buffer_IntBuffer_dump, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Buffer_UIntBuffer_methods[] = {
	ZEND_ME(GL_Buffer_UIntBuffer, __construct, arginfo_class_GL_Buffer_UIntBuffer___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UIntBuffer, __toString, arginfo_class_GL_Buffer_UIntBuffer___toString, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UIntBuffer, push, arginfo_class_GL_Buffer_UIntBuffer_push, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UIntBuffer, pushArray, arginfo_class_GL_Buffer_UIntBuffer_pushArray, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UIntBuffer, append, arginfo_class_GL_Buffer_UIntBuffer_append, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UIntBuffer, fill, arginfo_class_GL_Buffer_UIntBuffer_fill, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UIntBuffer, clear, arginfo_class_GL_Buffer_UIntBuffer_clear, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UIntBuffer, size, arginfo_class_GL_Buffer_UIntBuffer_size, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UIntBuffer, capacity, arginfo_class_GL_Buffer_UIntBuffer_capacity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UIntBuffer, reserve, arginfo_class_GL_Buffer_UIntBuffer_reserve, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UIntBuffer, dump, arginfo_class_GL_Buffer_UIntBuffer_dump, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Buffer_ShortBuffer_methods[] = {
	ZEND_ME(GL_Buffer_ShortBuffer, __construct, arginfo_class_GL_Buffer_ShortBuffer___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ShortBuffer, __toString, arginfo_class_GL_Buffer_ShortBuffer___toString, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ShortBuffer, push, arginfo_class_GL_Buffer_ShortBuffer_push, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ShortBuffer, pushArray, arginfo_class_GL_Buffer_ShortBuffer_pushArray, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ShortBuffer, append, arginfo_class_GL_Buffer_ShortBuffer_append, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ShortBuffer, fill, arginfo_class_GL_Buffer_ShortBuffer_fill, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ShortBuffer, clear, arginfo_class_GL_Buffer_ShortBuffer_clear, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ShortBuffer, size, arginfo_class_GL_Buffer_ShortBuffer_size, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ShortBuffer, capacity, arginfo_class_GL_Buffer_ShortBuffer_capacity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ShortBuffer, reserve, arginfo_class_GL_Buffer_ShortBuffer_reserve, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ShortBuffer, dump, arginfo_class_GL_Buffer_ShortBuffer_dump, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Buffer_UShortBuffer_methods[] = {
	ZEND_ME(GL_Buffer_UShortBuffer, __construct, arginfo_class_GL_Buffer_UShortBuffer___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UShortBuffer, __toString, arginfo_class_GL_Buffer_UShortBuffer___toString, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UShortBuffer, push, arginfo_class_GL_Buffer_UShortBuffer_push, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UShortBuffer, pushArray, arginfo_class_GL_Buffer_UShortBuffer_pushArray, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UShortBuffer, append, arginfo_class_GL_Buffer_UShortBuffer_append, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UShortBuffer, fill, arginfo_class_GL_Buffer_UShortBuffer_fill, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UShortBuffer, clear, arginfo_class_GL_Buffer_UShortBuffer_clear, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UShortBuffer, size, arginfo_class_GL_Buffer_UShortBuffer_size, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UShortBuffer, capacity, arginfo_class_GL_Buffer_UShortBuffer_capacity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UShortBuffer, reserve, arginfo_class_GL_Buffer_UShortBuffer_reserve, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UShortBuffer, dump, arginfo_class_GL_Buffer_UShortBuffer_dump, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Buffer_ByteBuffer_methods[] = {
	ZEND_ME(GL_Buffer_ByteBuffer, __construct, arginfo_class_GL_Buffer_ByteBuffer___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ByteBuffer, __toString, arginfo_class_GL_Buffer_ByteBuffer___toString, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ByteBuffer, push, arginfo_class_GL_Buffer_ByteBuffer_push, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ByteBuffer, pushArray, arginfo_class_GL_Buffer_ByteBuffer_pushArray, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ByteBuffer, append, arginfo_class_GL_Buffer_ByteBuffer_append, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ByteBuffer, fill, arginfo_class_GL_Buffer_ByteBuffer_fill, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ByteBuffer, clear, arginfo_class_GL_Buffer_ByteBuffer_clear, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ByteBuffer, size, arginfo_class_GL_Buffer_ByteBuffer_size, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ByteBuffer, capacity, arginfo_class_GL_Buffer_ByteBuffer_capacity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ByteBuffer, reserve, arginfo_class_GL_Buffer_ByteBuffer_reserve, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_ByteBuffer, dump, arginfo_class_GL_Buffer_ByteBuffer_dump, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Buffer_UByteBuffer_methods[] = {
	ZEND_ME(GL_Buffer_UByteBuffer, __construct, arginfo_class_GL_Buffer_UByteBuffer___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, __toString, arginfo_class_GL_Buffer_UByteBuffer___toString, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, push, arginfo_class_GL_Buffer_UByteBuffer_push, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, pushArray, arginfo_class_GL_Buffer_UByteBuffer_pushArray, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, append, arginfo_class_GL_Buffer_UByteBuffer_append, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, pushString, arginfo_class_GL_Buffer_UByteBuffer_pushString, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, fill, arginfo_class_GL_Buffer_UByteBuffer_fill, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, clear, arginfo_class_GL_Buffer_UByteBuffer_clear, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, size, arginfo_class_GL_Buffer_UByteBuffer_size, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, capacity, arginfo_class_GL_Buffer_UByteBuffer_capacity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, reserve, arginfo_class_GL_Buffer_UByteBuffer_reserve, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Buffer_UByteBuffer, dump, arginfo_class_GL_Buffer_UByteBuffer_dump, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_VectorGraphics_VGColor_methods[] = {
	ZEND_ME(GL_VectorGraphics_VGColor, rgb, arginfo_class_GL_VectorGraphics_VGColor_rgb, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, rgba, arginfo_class_GL_VectorGraphics_VGColor_rgba, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, hsl, arginfo_class_GL_VectorGraphics_VGColor_hsl, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, hsla, arginfo_class_GL_VectorGraphics_VGColor_hsla, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, irgb, arginfo_class_GL_VectorGraphics_VGColor_irgb, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, irgba, arginfo_class_GL_VectorGraphics_VGColor_irgba, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, hex, arginfo_class_GL_VectorGraphics_VGColor_hex, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, red, arginfo_class_GL_VectorGraphics_VGColor_red, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, green, arginfo_class_GL_VectorGraphics_VGColor_green, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, blue, arginfo_class_GL_VectorGraphics_VGColor_blue, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, white, arginfo_class_GL_VectorGraphics_VGColor_white, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, black, arginfo_class_GL_VectorGraphics_VGColor_black, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, transparent, arginfo_class_GL_VectorGraphics_VGColor_transparent, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, yellow, arginfo_class_GL_VectorGraphics_VGColor_yellow, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, cyan, arginfo_class_GL_VectorGraphics_VGColor_cyan, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, magenta, arginfo_class_GL_VectorGraphics_VGColor_magenta, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, orange, arginfo_class_GL_VectorGraphics_VGColor_orange, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, pink, arginfo_class_GL_VectorGraphics_VGColor_pink, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, purple, arginfo_class_GL_VectorGraphics_VGColor_purple, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, brown, arginfo_class_GL_VectorGraphics_VGColor_brown, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, gray, arginfo_class_GL_VectorGraphics_VGColor_gray, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, darkGray, arginfo_class_GL_VectorGraphics_VGColor_darkGray, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, lightGray, arginfo_class_GL_VectorGraphics_VGColor_lightGray, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, random, arginfo_class_GL_VectorGraphics_VGColor_random, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, randomGray, arginfo_class_GL_VectorGraphics_VGColor_randomGray, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_VGColor, __construct, arginfo_class_GL_VectorGraphics_VGColor___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGColor, getHSLA, arginfo_class_GL_VectorGraphics_VGColor_getHSLA, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGColor, getHSL, arginfo_class_GL_VectorGraphics_VGColor_getHSL, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGColor, getVec4, arginfo_class_GL_VectorGraphics_VGColor_getVec4, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGColor, getVec3, arginfo_class_GL_VectorGraphics_VGColor_getVec3, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGColor, darken, arginfo_class_GL_VectorGraphics_VGColor_darken, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGColor, lighten, arginfo_class_GL_VectorGraphics_VGColor_lighten, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGColor, invert, arginfo_class_GL_VectorGraphics_VGColor_invert, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGColor, withAlpha, arginfo_class_GL_VectorGraphics_VGColor_withAlpha, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGColor, copy, arginfo_class_GL_VectorGraphics_VGColor_copy, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGColor, contrast, arginfo_class_GL_VectorGraphics_VGColor_contrast, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_VectorGraphics_VGImage_methods[] = {
	ZEND_ME(GL_VectorGraphics_VGImage, makePaint, arginfo_class_GL_VectorGraphics_VGImage_makePaint, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGImage, makePaintCentered, arginfo_class_GL_VectorGraphics_VGImage_makePaintCentered, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_VectorGraphics_SVGImage_methods[] = {
	ZEND_ME(GL_VectorGraphics_SVGImage, fromDisk, arginfo_class_GL_VectorGraphics_SVGImage_fromDisk, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(GL_VectorGraphics_SVGImage, fromString, arginfo_class_GL_VectorGraphics_SVGImage_fromString, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_VectorGraphics_VGContext_methods[] = {
	ZEND_ME(GL_VectorGraphics_VGContext, __construct, arginfo_class_GL_VectorGraphics_VGContext___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, fillColori, arginfo_class_GL_VectorGraphics_VGContext_fillColori, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, strokeColori, arginfo_class_GL_VectorGraphics_VGContext_strokeColori, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, fillColorVec4, arginfo_class_GL_VectorGraphics_VGContext_fillColorVec4, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, strokeColorVec4, arginfo_class_GL_VectorGraphics_VGContext_strokeColorVec4, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, transformPoint, arginfo_class_GL_VectorGraphics_VGContext_transformPoint, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, transformVec2, arginfo_class_GL_VectorGraphics_VGContext_transformVec2, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, imageFromTexture, arginfo_class_GL_VectorGraphics_VGContext_imageFromTexture, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, imageFromHandle, arginfo_class_GL_VectorGraphics_VGContext_imageFromHandle, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, drawSVG, arginfo_class_GL_VectorGraphics_VGContext_drawSVG, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, linearGradient, arginfo_class_GL_VectorGraphics_VGContext_linearGradient, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, boxGradient, arginfo_class_GL_VectorGraphics_VGContext_boxGradient, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, radialGradient, arginfo_class_GL_VectorGraphics_VGContext_radialGradient, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, beginFrame, arginfo_class_GL_VectorGraphics_VGContext_beginFrame, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, cancelFrame, arginfo_class_GL_VectorGraphics_VGContext_cancelFrame, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, endFrame, arginfo_class_GL_VectorGraphics_VGContext_endFrame, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, globalCompositeOperation, arginfo_class_GL_VectorGraphics_VGContext_globalCompositeOperation, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, globalCompositeBlendFunc, arginfo_class_GL_VectorGraphics_VGContext_globalCompositeBlendFunc, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, globalCompositeBlendFuncSeparate, arginfo_class_GL_VectorGraphics_VGContext_globalCompositeBlendFuncSeparate, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, save, arginfo_class_GL_VectorGraphics_VGContext_save, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, restore, arginfo_class_GL_VectorGraphics_VGContext_restore, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, reset, arginfo_class_GL_VectorGraphics_VGContext_reset, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, shapeAntiAlias, arginfo_class_GL_VectorGraphics_VGContext_shapeAntiAlias, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, strokeColor, arginfo_class_GL_VectorGraphics_VGContext_strokeColor, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, strokePaint, arginfo_class_GL_VectorGraphics_VGContext_strokePaint, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, fillColor, arginfo_class_GL_VectorGraphics_VGContext_fillColor, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, fillPaint, arginfo_class_GL_VectorGraphics_VGContext_fillPaint, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, miterLimit, arginfo_class_GL_VectorGraphics_VGContext_miterLimit, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, strokeWidth, arginfo_class_GL_VectorGraphics_VGContext_strokeWidth, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, lineCap, arginfo_class_GL_VectorGraphics_VGContext_lineCap, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, lineJoin, arginfo_class_GL_VectorGraphics_VGContext_lineJoin, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, globalAlpha, arginfo_class_GL_VectorGraphics_VGContext_globalAlpha, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, resetTransform, arginfo_class_GL_VectorGraphics_VGContext_resetTransform, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, transform, arginfo_class_GL_VectorGraphics_VGContext_transform, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, translate, arginfo_class_GL_VectorGraphics_VGContext_translate, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, rotate, arginfo_class_GL_VectorGraphics_VGContext_rotate, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, skewX, arginfo_class_GL_VectorGraphics_VGContext_skewX, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, skewY, arginfo_class_GL_VectorGraphics_VGContext_skewY, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, scale, arginfo_class_GL_VectorGraphics_VGContext_scale, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, currentTransform, arginfo_class_GL_VectorGraphics_VGContext_currentTransform, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, transformPointCurrent, arginfo_class_GL_VectorGraphics_VGContext_transformPointCurrent, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, imageSize, arginfo_class_GL_VectorGraphics_VGContext_imageSize, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, deleteImage, arginfo_class_GL_VectorGraphics_VGContext_deleteImage, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, scissor, arginfo_class_GL_VectorGraphics_VGContext_scissor, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, intersectScissor, arginfo_class_GL_VectorGraphics_VGContext_intersectScissor, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, resetScissor, arginfo_class_GL_VectorGraphics_VGContext_resetScissor, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, beginPath, arginfo_class_GL_VectorGraphics_VGContext_beginPath, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, moveTo, arginfo_class_GL_VectorGraphics_VGContext_moveTo, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, lineTo, arginfo_class_GL_VectorGraphics_VGContext_lineTo, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, bezierTo, arginfo_class_GL_VectorGraphics_VGContext_bezierTo, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, quadTo, arginfo_class_GL_VectorGraphics_VGContext_quadTo, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, arcTo, arginfo_class_GL_VectorGraphics_VGContext_arcTo, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, closePath, arginfo_class_GL_VectorGraphics_VGContext_closePath, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, pathWinding, arginfo_class_GL_VectorGraphics_VGContext_pathWinding, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, arc, arginfo_class_GL_VectorGraphics_VGContext_arc, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, rect, arginfo_class_GL_VectorGraphics_VGContext_rect, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, roundedRect, arginfo_class_GL_VectorGraphics_VGContext_roundedRect, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, roundedRectVarying, arginfo_class_GL_VectorGraphics_VGContext_roundedRectVarying, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, ellipse, arginfo_class_GL_VectorGraphics_VGContext_ellipse, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, circle, arginfo_class_GL_VectorGraphics_VGContext_circle, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, fill, arginfo_class_GL_VectorGraphics_VGContext_fill, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, fillEvenOdd, arginfo_class_GL_VectorGraphics_VGContext_fillEvenOdd, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, stroke, arginfo_class_GL_VectorGraphics_VGContext_stroke, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, clip, arginfo_class_GL_VectorGraphics_VGContext_clip, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, resetClip, arginfo_class_GL_VectorGraphics_VGContext_resetClip, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, saveClip, arginfo_class_GL_VectorGraphics_VGContext_saveClip, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, restoreClip, arginfo_class_GL_VectorGraphics_VGContext_restoreClip, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, createFont, arginfo_class_GL_VectorGraphics_VGContext_createFont, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, createFontAtIndex, arginfo_class_GL_VectorGraphics_VGContext_createFontAtIndex, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, findFont, arginfo_class_GL_VectorGraphics_VGContext_findFont, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, addFallbackFontId, arginfo_class_GL_VectorGraphics_VGContext_addFallbackFontId, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, addFallbackFont, arginfo_class_GL_VectorGraphics_VGContext_addFallbackFont, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, resetFallbackFontsId, arginfo_class_GL_VectorGraphics_VGContext_resetFallbackFontsId, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, resetFallbackFonts, arginfo_class_GL_VectorGraphics_VGContext_resetFallbackFonts, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, fontSize, arginfo_class_GL_VectorGraphics_VGContext_fontSize, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, fontBlur, arginfo_class_GL_VectorGraphics_VGContext_fontBlur, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, textLetterSpacing, arginfo_class_GL_VectorGraphics_VGContext_textLetterSpacing, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, textLineHeight, arginfo_class_GL_VectorGraphics_VGContext_textLineHeight, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, textAlign, arginfo_class_GL_VectorGraphics_VGContext_textAlign, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, fontFaceId, arginfo_class_GL_VectorGraphics_VGContext_fontFaceId, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, fontFace, arginfo_class_GL_VectorGraphics_VGContext_fontFace, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, text, arginfo_class_GL_VectorGraphics_VGContext_text, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, textBox, arginfo_class_GL_VectorGraphics_VGContext_textBox, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, textBounds, arginfo_class_GL_VectorGraphics_VGContext_textBounds, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, textBoxBounds, arginfo_class_GL_VectorGraphics_VGContext_textBoxBounds, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, textMetrics, arginfo_class_GL_VectorGraphics_VGContext_textMetrics, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, deleteInternal, arginfo_class_GL_VectorGraphics_VGContext_deleteInternal, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_VectorGraphics_VGContext, debugDumpPathCache, arginfo_class_GL_VectorGraphics_VGContext_debugDumpPathCache, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Audio_Engine_methods[] = {
	ZEND_ME(GL_Audio_Engine, __construct, arginfo_class_GL_Audio_Engine___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Engine, start, arginfo_class_GL_Audio_Engine_start, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Engine, stop, arginfo_class_GL_Audio_Engine_stop, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Engine, soundFromDisk, arginfo_class_GL_Audio_Engine_soundFromDisk, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Engine, setMasterVolume, arginfo_class_GL_Audio_Engine_setMasterVolume, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Engine, getMasterVolume, arginfo_class_GL_Audio_Engine_getMasterVolume, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Engine, setListenerPosition, arginfo_class_GL_Audio_Engine_setListenerPosition, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Engine, setListenerDirection, arginfo_class_GL_Audio_Engine_setListenerDirection, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Engine, setListenerWorldUp, arginfo_class_GL_Audio_Engine_setListenerWorldUp, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_GL_Audio_Sound_methods[] = {
	ZEND_ME(GL_Audio_Sound, __construct, arginfo_class_GL_Audio_Sound___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getSampleRate, arginfo_class_GL_Audio_Sound_getSampleRate, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getChannels, arginfo_class_GL_Audio_Sound_getChannels, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getLength, arginfo_class_GL_Audio_Sound_getLength, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getLengthPCM, arginfo_class_GL_Audio_Sound_getLengthPCM, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, play, arginfo_class_GL_Audio_Sound_play, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, stop, arginfo_class_GL_Audio_Sound_stop, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setStartMs, arginfo_class_GL_Audio_Sound_setStartMs, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setStopMs, arginfo_class_GL_Audio_Sound_setStopMs, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, isPlaying, arginfo_class_GL_Audio_Sound_isPlaying, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getCursor, arginfo_class_GL_Audio_Sound_getCursor, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getCursorPCM, arginfo_class_GL_Audio_Sound_getCursorPCM, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, seekTo, arginfo_class_GL_Audio_Sound_seekTo, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, seekToPCM, arginfo_class_GL_Audio_Sound_seekToPCM, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setVolume, arginfo_class_GL_Audio_Sound_setVolume, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setPitch, arginfo_class_GL_Audio_Sound_setPitch, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setLoop, arginfo_class_GL_Audio_Sound_setLoop, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setPosition, arginfo_class_GL_Audio_Sound_setPosition, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setMinDistance, arginfo_class_GL_Audio_Sound_setMinDistance, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getMinDistance, arginfo_class_GL_Audio_Sound_getMinDistance, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setMaxDistance, arginfo_class_GL_Audio_Sound_setMaxDistance, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getMaxDistance, arginfo_class_GL_Audio_Sound_getMaxDistance, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setDirection, arginfo_class_GL_Audio_Sound_setDirection, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getDirection, arginfo_class_GL_Audio_Sound_getDirection, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setVelocity, arginfo_class_GL_Audio_Sound_setVelocity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getVelocity, arginfo_class_GL_Audio_Sound_getVelocity, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, fadeIn, arginfo_class_GL_Audio_Sound_fadeIn, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, fadeOut, arginfo_class_GL_Audio_Sound_fadeOut, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, setFade, arginfo_class_GL_Audio_Sound_setFade, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, getCurrentFadeVolume, arginfo_class_GL_Audio_Sound_getCurrentFadeVolume, ZEND_ACC_PUBLIC)
	ZEND_ME(GL_Audio_Sound, readFrames, arginfo_class_GL_Audio_Sound_readFrames, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static zend_class_entry *register_class_GL_Texture_Texture2D(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Texture", "Texture2D", class_GL_Texture_Texture2D_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_Noise(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL", "Noise", class_GL_Noise_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_Math_GLM(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Math", "GLM", class_GL_Math_GLM_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_Math_Vec2(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Math", "Vec2", class_GL_Math_Vec2_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_Math_Vec3(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Math", "Vec3", class_GL_Math_Vec3_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_Math_Vec4(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Math", "Vec4", class_GL_Math_Vec4_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_Math_Quat(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Math", "Quat", class_GL_Math_Quat_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_Math_Mat4(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Math", "Mat4", class_GL_Math_Mat4_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_Buffer_BufferInterface(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Buffer", "BufferInterface", class_GL_Buffer_BufferInterface_methods);
	class_entry = zend_register_internal_interface(&ce);

	return class_entry;
}

static zend_class_entry *register_class_GL_Buffer_FloatBuffer(zend_class_entry *class_entry_GL_Buffer_BufferInterface)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Buffer", "FloatBuffer", class_GL_Buffer_FloatBuffer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);
	zend_class_implements(class_entry, 1, class_entry_GL_Buffer_BufferInterface);

	return class_entry;
}

static zend_class_entry *register_class_GL_Buffer_HFloatBuffer(zend_class_entry *class_entry_GL_Buffer_BufferInterface)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Buffer", "HFloatBuffer", class_GL_Buffer_HFloatBuffer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);
	zend_class_implements(class_entry, 1, class_entry_GL_Buffer_BufferInterface);

	return class_entry;
}

static zend_class_entry *register_class_GL_Buffer_DoubleBuffer(zend_class_entry *class_entry_GL_Buffer_BufferInterface)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Buffer", "DoubleBuffer", class_GL_Buffer_DoubleBuffer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);
	zend_class_implements(class_entry, 1, class_entry_GL_Buffer_BufferInterface);

	return class_entry;
}

static zend_class_entry *register_class_GL_Buffer_IntBuffer(zend_class_entry *class_entry_GL_Buffer_BufferInterface)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Buffer", "IntBuffer", class_GL_Buffer_IntBuffer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);
	zend_class_implements(class_entry, 1, class_entry_GL_Buffer_BufferInterface);

	return class_entry;
}

static zend_class_entry *register_class_GL_Buffer_UIntBuffer(zend_class_entry *class_entry_GL_Buffer_BufferInterface)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Buffer", "UIntBuffer", class_GL_Buffer_UIntBuffer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);
	zend_class_implements(class_entry, 1, class_entry_GL_Buffer_BufferInterface);

	return class_entry;
}

static zend_class_entry *register_class_GL_Buffer_ShortBuffer(zend_class_entry *class_entry_GL_Buffer_BufferInterface)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Buffer", "ShortBuffer", class_GL_Buffer_ShortBuffer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);
	zend_class_implements(class_entry, 1, class_entry_GL_Buffer_BufferInterface);

	return class_entry;
}

static zend_class_entry *register_class_GL_Buffer_UShortBuffer(zend_class_entry *class_entry_GL_Buffer_BufferInterface)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Buffer", "UShortBuffer", class_GL_Buffer_UShortBuffer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);
	zend_class_implements(class_entry, 1, class_entry_GL_Buffer_BufferInterface);

	return class_entry;
}

static zend_class_entry *register_class_GL_Buffer_ByteBuffer(zend_class_entry *class_entry_GL_Buffer_BufferInterface)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Buffer", "ByteBuffer", class_GL_Buffer_ByteBuffer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);
	zend_class_implements(class_entry, 1, class_entry_GL_Buffer_BufferInterface);

	return class_entry;
}

static zend_class_entry *register_class_GL_Buffer_UByteBuffer(zend_class_entry *class_entry_GL_Buffer_BufferInterface)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Buffer", "UByteBuffer", class_GL_Buffer_UByteBuffer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);
	zend_class_implements(class_entry, 1, class_entry_GL_Buffer_BufferInterface);

	return class_entry;
}

static zend_class_entry *register_class_GL_VectorGraphics_VGColor(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\VectorGraphics", "VGColor", class_GL_VectorGraphics_VGColor_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	zval property_r_default_value;
	ZVAL_UNDEF(&property_r_default_value);
	zend_string *property_r_name = zend_string_init("r", sizeof("r") - 1, 1);
	zend_declare_typed_property(class_entry, property_r_name, &property_r_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_r_name);

	zval property_g_default_value;
	ZVAL_UNDEF(&property_g_default_value);
	zend_string *property_g_name = zend_string_init("g", sizeof("g") - 1, 1);
	zend_declare_typed_property(class_entry, property_g_name, &property_g_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_g_name);

	zval property_b_default_value;
	ZVAL_UNDEF(&property_b_default_value);
	zend_string *property_b_name = zend_string_init("b", sizeof("b") - 1, 1);
	zend_declare_typed_property(class_entry, property_b_name, &property_b_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_b_name);

	zval property_a_default_value;
	ZVAL_UNDEF(&property_a_default_value);
	zend_string *property_a_name = zend_string_init("a", sizeof("a") - 1, 1);
	zend_declare_typed_property(class_entry, property_a_name, &property_a_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_a_name);

	return class_entry;
}

static zend_class_entry *register_class_GL_VectorGraphics_VGPaint(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\VectorGraphics", "VGPaint", NULL);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_VectorGraphics_VGImage(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\VectorGraphics", "VGImage", class_GL_VectorGraphics_VGImage_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_VectorGraphics_SVGImage(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\VectorGraphics", "SVGImage", class_GL_VectorGraphics_SVGImage_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	zval property_width_default_value;
	ZVAL_UNDEF(&property_width_default_value);
	zend_string *property_width_name = zend_string_init("width", sizeof("width") - 1, 1);
	zend_declare_typed_property(class_entry, property_width_name, &property_width_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_width_name);

	zval property_height_default_value;
	ZVAL_UNDEF(&property_height_default_value);
	zend_string *property_height_name = zend_string_init("height", sizeof("height") - 1, 1);
	zend_declare_typed_property(class_entry, property_height_name, &property_height_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_height_name);

	return class_entry;
}

static zend_class_entry *register_class_GL_VectorGraphics_VGAlign(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\VectorGraphics", "VGAlign", NULL);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_VectorGraphics_VGContext(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\VectorGraphics", "VGContext", class_GL_VectorGraphics_VGContext_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_Audio_Engine(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Audio", "Engine", class_GL_Audio_Engine_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	return class_entry;
}

static zend_class_entry *register_class_GL_Audio_Sound(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "GL\\Audio", "Sound", class_GL_Audio_Sound_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	zval property_sampleRate_default_value;
	ZVAL_UNDEF(&property_sampleRate_default_value);
	zend_string *property_sampleRate_name = zend_string_init("sampleRate", sizeof("sampleRate") - 1, 1);
	zend_declare_typed_property(class_entry, property_sampleRate_name, &property_sampleRate_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_sampleRate_name);

	zval property_channels_default_value;
	ZVAL_UNDEF(&property_channels_default_value);
	zend_string *property_channels_name = zend_string_init("channels", sizeof("channels") - 1, 1);
	zend_declare_typed_property(class_entry, property_channels_name, &property_channels_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_channels_name);

	zval property_length_default_value;
	ZVAL_UNDEF(&property_length_default_value);
	zend_string *property_length_name = zend_string_init("length", sizeof("length") - 1, 1);
	zend_declare_typed_property(class_entry, property_length_name, &property_length_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_DOUBLE));
	zend_string_release(property_length_name);

	zval property_lengthPCM_default_value;
	ZVAL_UNDEF(&property_lengthPCM_default_value);
	zend_string *property_lengthPCM_name = zend_string_init("lengthPCM", sizeof("lengthPCM") - 1, 1);
	zend_declare_typed_property(class_entry, property_lengthPCM_name, &property_lengthPCM_default_value, ZEND_ACC_PUBLIC|ZEND_ACC_READONLY, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release(property_lengthPCM_name);

	return class_entry;
}
