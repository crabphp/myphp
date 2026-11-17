/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 9a3b3d94085dd226493be353867d7770ed7f4ad6 */

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_PhpSdl_Window___construct, 0, 0, 3)
	ZEND_ARG_TYPE_INFO(0, title, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_size, 0, 0, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Window_pixelSize arginfo_class_PhpSdl_Window_size

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_setSize, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_setTitle, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, title, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_setIcon, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_begin, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, clear, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_swap, 0, 0, IS_VOID, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_beginCapture, 0, 3, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, clear, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_endCapture, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_vsync, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, on, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Window_raise arginfo_class_PhpSdl_Window_swap

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_focused, 0, 0, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_setCursor, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, cursor, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_startTextInput, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Window_stopTextInput arginfo_class_PhpSdl_Window_swap

#define arginfo_class_PhpSdl_Window_close arginfo_class_PhpSdl_Window_swap

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Text_addFont, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, index, IS_LONG, 0, "0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Text_addFallback, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, index, IS_LONG, 0, "0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Text_width, 0, 3, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, font, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, size, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, tracking, IS_DOUBLE, 0, "0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Text_carets, 0, 3, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, font, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, size, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, tracking, IS_DOUBLE, 0, "0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Text_metrics, 0, 2, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, font, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, size, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Text_measure arginfo_class_PhpSdl_Text_carets

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Renderer_begin, 0, 3, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, ratio, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Renderer_box, 0, 6, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radii, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, top, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, bottom, IS_ARRAY, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, border, IS_ARRAY, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, borderWidth, IS_DOUBLE, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, clip, IS_ARRAY, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Renderer_shadow, 0, 7, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radii, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, color, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, blur, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, spread, IS_DOUBLE, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, offsetY, IS_DOUBLE, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, clip, IS_ARRAY, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Renderer_text, 0, 6, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, font, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, size, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, baseline, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, color, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, tracking, IS_DOUBLE, 0, "0")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, clip, IS_ARRAY, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Renderer_icon, 0, 5, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, svg, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, size, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, color, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, clip, IS_ARRAY, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Renderer_image, 0, 7, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, box, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, radii, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, opacity, IS_DOUBLE, 0, "1")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, clip, IS_ARRAY, 1, "null")
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, source, IS_ARRAY, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Renderer_path, 0, 2, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, path, PhpSdl\\Path, 0)
	ZEND_ARG_TYPE_INFO(0, style, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Renderer_flush arginfo_class_PhpSdl_Window_swap

#define arginfo_class_PhpSdl_Renderer_forget arginfo_class_PhpSdl_Window_swap

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Renderer_imageSize, 0, 1, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Renderer_resizeImage, 0, 4, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, from, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, to, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_moveTo, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Path_lineTo arginfo_class_PhpSdl_Path_moveTo

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_quadraticCurveTo, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, cpx, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, cpy, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_bezierCurveTo, 0, 6, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, cp1x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, cp1y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, cp2x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, cp2y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_arc, 0, 5, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radius, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, startAngle, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, endAngle, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, counterclockwise, _IS_BOOL, 0, "false")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_arcTo, 0, 5, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x1, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y1, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x2, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y2, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radius, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_ellipse, 0, 7, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radiusX, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, radiusY, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, rotation, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, startAngle, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, endAngle, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, counterclockwise, _IS_BOOL, 0, "false")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_rect, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_roundRect, 0, 4, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, width, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, height, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_MASK(0, radii, MAY_BE_DOUBLE|MAY_BE_ARRAY, "0")
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Path_closePath arginfo_class_PhpSdl_Window_swap

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_addPath, 0, 1, IS_VOID, 0)
	ZEND_ARG_OBJ_INFO(0, path, PhpSdl\\Path, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, transform, IS_ARRAY, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_text, 0, 5, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, font, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, size, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, tracking, IS_DOUBLE, 0, "0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_setTransform, 0, 0, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, matrix, IS_ARRAY, 1, "null")
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Path_clear arginfo_class_PhpSdl_Window_swap

#define arginfo_class_PhpSdl_Path_isEmpty arginfo_class_PhpSdl_Window_focused

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_contains, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, rule, IS_LONG, 0, "0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_containsStroke, 0, 3, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, stroke, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Path_bounds, 0, 0, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_PhpSdl_Sound___construct, 0, 0, 1)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Sound_play arginfo_class_PhpSdl_Window_swap

#define arginfo_class_PhpSdl_Sound_pause arginfo_class_PhpSdl_Window_swap

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sound_seek, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, seconds, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sound_position, 0, 0, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Sound_duration arginfo_class_PhpSdl_Sound_position

#define arginfo_class_PhpSdl_Sound_playing arginfo_class_PhpSdl_Window_focused

#define arginfo_class_PhpSdl_Sound_ended arginfo_class_PhpSdl_Window_focused

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sound_volume, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, volume, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sound_loop, 0, 0, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, on, _IS_BOOL, 0, "true")
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Sound_available arginfo_class_PhpSdl_Window_focused

#define arginfo_class_PhpSdl_Sound_masterVolume arginfo_class_PhpSdl_Sound_volume

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sound_length, 0, 1, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sound_peaks, 0, 2, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, path, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, count, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sdl_events, 0, 1, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, timeout, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Sdl_time arginfo_class_PhpSdl_Sound_position

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sdl_clipboard, 0, 0, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sdl_setClipboard, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Sdl_version arginfo_class_PhpSdl_Sdl_clipboard

#define arginfo_class_PhpSdl_Sdl_backend arginfo_class_PhpSdl_Sdl_clipboard

ZEND_METHOD(PhpSdl_Window, __construct);
ZEND_METHOD(PhpSdl_Window, size);
ZEND_METHOD(PhpSdl_Window, pixelSize);
ZEND_METHOD(PhpSdl_Window, setSize);
ZEND_METHOD(PhpSdl_Window, setTitle);
ZEND_METHOD(PhpSdl_Window, setIcon);
ZEND_METHOD(PhpSdl_Window, begin);
ZEND_METHOD(PhpSdl_Window, swap);
ZEND_METHOD(PhpSdl_Window, beginCapture);
ZEND_METHOD(PhpSdl_Window, endCapture);
ZEND_METHOD(PhpSdl_Window, vsync);
ZEND_METHOD(PhpSdl_Window, raise);
ZEND_METHOD(PhpSdl_Window, focused);
ZEND_METHOD(PhpSdl_Window, setCursor);
ZEND_METHOD(PhpSdl_Window, startTextInput);
ZEND_METHOD(PhpSdl_Window, stopTextInput);
ZEND_METHOD(PhpSdl_Window, close);
ZEND_METHOD(PhpSdl_Text, addFont);
ZEND_METHOD(PhpSdl_Text, addFallback);
ZEND_METHOD(PhpSdl_Text, width);
ZEND_METHOD(PhpSdl_Text, carets);
ZEND_METHOD(PhpSdl_Text, metrics);
ZEND_METHOD(PhpSdl_Text, measure);
ZEND_METHOD(PhpSdl_Renderer, begin);
ZEND_METHOD(PhpSdl_Renderer, box);
ZEND_METHOD(PhpSdl_Renderer, shadow);
ZEND_METHOD(PhpSdl_Renderer, text);
ZEND_METHOD(PhpSdl_Renderer, icon);
ZEND_METHOD(PhpSdl_Renderer, image);
ZEND_METHOD(PhpSdl_Renderer, path);
ZEND_METHOD(PhpSdl_Renderer, flush);
ZEND_METHOD(PhpSdl_Renderer, forget);
ZEND_METHOD(PhpSdl_Renderer, imageSize);
ZEND_METHOD(PhpSdl_Renderer, resizeImage);
ZEND_METHOD(PhpSdl_Path, moveTo);
ZEND_METHOD(PhpSdl_Path, lineTo);
ZEND_METHOD(PhpSdl_Path, quadraticCurveTo);
ZEND_METHOD(PhpSdl_Path, bezierCurveTo);
ZEND_METHOD(PhpSdl_Path, arc);
ZEND_METHOD(PhpSdl_Path, arcTo);
ZEND_METHOD(PhpSdl_Path, ellipse);
ZEND_METHOD(PhpSdl_Path, rect);
ZEND_METHOD(PhpSdl_Path, roundRect);
ZEND_METHOD(PhpSdl_Path, closePath);
ZEND_METHOD(PhpSdl_Path, addPath);
ZEND_METHOD(PhpSdl_Path, text);
ZEND_METHOD(PhpSdl_Path, setTransform);
ZEND_METHOD(PhpSdl_Path, clear);
ZEND_METHOD(PhpSdl_Path, isEmpty);
ZEND_METHOD(PhpSdl_Path, contains);
ZEND_METHOD(PhpSdl_Path, containsStroke);
ZEND_METHOD(PhpSdl_Path, bounds);
ZEND_METHOD(PhpSdl_Sound, __construct);
ZEND_METHOD(PhpSdl_Sound, play);
ZEND_METHOD(PhpSdl_Sound, pause);
ZEND_METHOD(PhpSdl_Sound, seek);
ZEND_METHOD(PhpSdl_Sound, position);
ZEND_METHOD(PhpSdl_Sound, duration);
ZEND_METHOD(PhpSdl_Sound, playing);
ZEND_METHOD(PhpSdl_Sound, ended);
ZEND_METHOD(PhpSdl_Sound, volume);
ZEND_METHOD(PhpSdl_Sound, loop);
ZEND_METHOD(PhpSdl_Sound, available);
ZEND_METHOD(PhpSdl_Sound, masterVolume);
ZEND_METHOD(PhpSdl_Sound, length);
ZEND_METHOD(PhpSdl_Sound, peaks);
ZEND_METHOD(PhpSdl_Sdl, events);
ZEND_METHOD(PhpSdl_Sdl, time);
ZEND_METHOD(PhpSdl_Sdl, clipboard);
ZEND_METHOD(PhpSdl_Sdl, setClipboard);
ZEND_METHOD(PhpSdl_Sdl, version);
ZEND_METHOD(PhpSdl_Sdl, backend);

static const zend_function_entry class_PhpSdl_Window_methods[] = {
	ZEND_ME(PhpSdl_Window, __construct, arginfo_class_PhpSdl_Window___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, size, arginfo_class_PhpSdl_Window_size, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, pixelSize, arginfo_class_PhpSdl_Window_pixelSize, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, setSize, arginfo_class_PhpSdl_Window_setSize, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, setTitle, arginfo_class_PhpSdl_Window_setTitle, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, setIcon, arginfo_class_PhpSdl_Window_setIcon, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, begin, arginfo_class_PhpSdl_Window_begin, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, swap, arginfo_class_PhpSdl_Window_swap, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, beginCapture, arginfo_class_PhpSdl_Window_beginCapture, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, endCapture, arginfo_class_PhpSdl_Window_endCapture, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, vsync, arginfo_class_PhpSdl_Window_vsync, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, raise, arginfo_class_PhpSdl_Window_raise, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, focused, arginfo_class_PhpSdl_Window_focused, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, setCursor, arginfo_class_PhpSdl_Window_setCursor, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, startTextInput, arginfo_class_PhpSdl_Window_startTextInput, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, stopTextInput, arginfo_class_PhpSdl_Window_stopTextInput, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, close, arginfo_class_PhpSdl_Window_close, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_PhpSdl_Text_methods[] = {
	ZEND_ME(PhpSdl_Text, addFont, arginfo_class_PhpSdl_Text_addFont, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Text, addFallback, arginfo_class_PhpSdl_Text_addFallback, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Text, width, arginfo_class_PhpSdl_Text_width, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Text, carets, arginfo_class_PhpSdl_Text_carets, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Text, metrics, arginfo_class_PhpSdl_Text_metrics, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Text, measure, arginfo_class_PhpSdl_Text_measure, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static const zend_function_entry class_PhpSdl_Renderer_methods[] = {
	ZEND_ME(PhpSdl_Renderer, begin, arginfo_class_PhpSdl_Renderer_begin, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Renderer, box, arginfo_class_PhpSdl_Renderer_box, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Renderer, shadow, arginfo_class_PhpSdl_Renderer_shadow, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Renderer, text, arginfo_class_PhpSdl_Renderer_text, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Renderer, icon, arginfo_class_PhpSdl_Renderer_icon, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Renderer, image, arginfo_class_PhpSdl_Renderer_image, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Renderer, path, arginfo_class_PhpSdl_Renderer_path, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Renderer, flush, arginfo_class_PhpSdl_Renderer_flush, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Renderer, forget, arginfo_class_PhpSdl_Renderer_forget, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Renderer, imageSize, arginfo_class_PhpSdl_Renderer_imageSize, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Renderer, resizeImage, arginfo_class_PhpSdl_Renderer_resizeImage, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static const zend_function_entry class_PhpSdl_Path_methods[] = {
	ZEND_ME(PhpSdl_Path, moveTo, arginfo_class_PhpSdl_Path_moveTo, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, lineTo, arginfo_class_PhpSdl_Path_lineTo, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, quadraticCurveTo, arginfo_class_PhpSdl_Path_quadraticCurveTo, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, bezierCurveTo, arginfo_class_PhpSdl_Path_bezierCurveTo, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, arc, arginfo_class_PhpSdl_Path_arc, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, arcTo, arginfo_class_PhpSdl_Path_arcTo, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, ellipse, arginfo_class_PhpSdl_Path_ellipse, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, rect, arginfo_class_PhpSdl_Path_rect, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, roundRect, arginfo_class_PhpSdl_Path_roundRect, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, closePath, arginfo_class_PhpSdl_Path_closePath, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, addPath, arginfo_class_PhpSdl_Path_addPath, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, text, arginfo_class_PhpSdl_Path_text, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, setTransform, arginfo_class_PhpSdl_Path_setTransform, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, clear, arginfo_class_PhpSdl_Path_clear, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, isEmpty, arginfo_class_PhpSdl_Path_isEmpty, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, contains, arginfo_class_PhpSdl_Path_contains, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, containsStroke, arginfo_class_PhpSdl_Path_containsStroke, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Path, bounds, arginfo_class_PhpSdl_Path_bounds, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_PhpSdl_Sound_methods[] = {
	ZEND_ME(PhpSdl_Sound, __construct, arginfo_class_PhpSdl_Sound___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Sound, play, arginfo_class_PhpSdl_Sound_play, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Sound, pause, arginfo_class_PhpSdl_Sound_pause, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Sound, seek, arginfo_class_PhpSdl_Sound_seek, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Sound, position, arginfo_class_PhpSdl_Sound_position, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Sound, duration, arginfo_class_PhpSdl_Sound_duration, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Sound, playing, arginfo_class_PhpSdl_Sound_playing, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Sound, ended, arginfo_class_PhpSdl_Sound_ended, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Sound, volume, arginfo_class_PhpSdl_Sound_volume, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Sound, loop, arginfo_class_PhpSdl_Sound_loop, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Sound, available, arginfo_class_PhpSdl_Sound_available, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sound, masterVolume, arginfo_class_PhpSdl_Sound_masterVolume, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sound, length, arginfo_class_PhpSdl_Sound_length, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sound, peaks, arginfo_class_PhpSdl_Sound_peaks, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static const zend_function_entry class_PhpSdl_Sdl_methods[] = {
	ZEND_ME(PhpSdl_Sdl, events, arginfo_class_PhpSdl_Sdl_events, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sdl, time, arginfo_class_PhpSdl_Sdl_time, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sdl, clipboard, arginfo_class_PhpSdl_Sdl_clipboard, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sdl, setClipboard, arginfo_class_PhpSdl_Sdl_setClipboard, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sdl, version, arginfo_class_PhpSdl_Sdl_version, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sdl, backend, arginfo_class_PhpSdl_Sdl_backend, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static zend_class_entry *register_class_PhpSdl_Window(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "PhpSdl", "Window", class_PhpSdl_Window_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL);

	return class_entry;
}

static zend_class_entry *register_class_PhpSdl_Text(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "PhpSdl", "Text", class_PhpSdl_Text_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL);

	return class_entry;
}

static zend_class_entry *register_class_PhpSdl_Renderer(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "PhpSdl", "Renderer", class_PhpSdl_Renderer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL);

	return class_entry;
}

static zend_class_entry *register_class_PhpSdl_Path(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "PhpSdl", "Path", class_PhpSdl_Path_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL);

	return class_entry;
}

static zend_class_entry *register_class_PhpSdl_Sound(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "PhpSdl", "Sound", class_PhpSdl_Sound_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL);

	return class_entry;
}

static zend_class_entry *register_class_PhpSdl_Sdl(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "PhpSdl", "Sdl", class_PhpSdl_Sdl_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL);

	return class_entry;
}
