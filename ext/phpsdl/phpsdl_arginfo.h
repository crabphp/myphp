/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 38792d0f3c018db37e142c6e7b41dd4a95c0c753 */

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

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Window_swap, 0, 0, IS_VOID, 0)
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

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sdl_events, 0, 1, IS_ARRAY, 0)
	ZEND_ARG_TYPE_INFO(0, timeout, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sdl_time, 0, 0, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sdl_clipboard, 0, 0, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_PhpSdl_Sdl_setClipboard, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_PhpSdl_Sdl_version arginfo_class_PhpSdl_Sdl_clipboard

ZEND_METHOD(PhpSdl_Window, __construct);
ZEND_METHOD(PhpSdl_Window, size);
ZEND_METHOD(PhpSdl_Window, pixelSize);
ZEND_METHOD(PhpSdl_Window, setSize);
ZEND_METHOD(PhpSdl_Window, setTitle);
ZEND_METHOD(PhpSdl_Window, swap);
ZEND_METHOD(PhpSdl_Window, vsync);
ZEND_METHOD(PhpSdl_Window, raise);
ZEND_METHOD(PhpSdl_Window, focused);
ZEND_METHOD(PhpSdl_Window, setCursor);
ZEND_METHOD(PhpSdl_Window, startTextInput);
ZEND_METHOD(PhpSdl_Window, stopTextInput);
ZEND_METHOD(PhpSdl_Window, close);
ZEND_METHOD(PhpSdl_Sdl, events);
ZEND_METHOD(PhpSdl_Sdl, time);
ZEND_METHOD(PhpSdl_Sdl, clipboard);
ZEND_METHOD(PhpSdl_Sdl, setClipboard);
ZEND_METHOD(PhpSdl_Sdl, version);

static const zend_function_entry class_PhpSdl_Window_methods[] = {
	ZEND_ME(PhpSdl_Window, __construct, arginfo_class_PhpSdl_Window___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, size, arginfo_class_PhpSdl_Window_size, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, pixelSize, arginfo_class_PhpSdl_Window_pixelSize, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, setSize, arginfo_class_PhpSdl_Window_setSize, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, setTitle, arginfo_class_PhpSdl_Window_setTitle, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, swap, arginfo_class_PhpSdl_Window_swap, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, vsync, arginfo_class_PhpSdl_Window_vsync, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, raise, arginfo_class_PhpSdl_Window_raise, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, focused, arginfo_class_PhpSdl_Window_focused, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, setCursor, arginfo_class_PhpSdl_Window_setCursor, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, startTextInput, arginfo_class_PhpSdl_Window_startTextInput, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, stopTextInput, arginfo_class_PhpSdl_Window_stopTextInput, ZEND_ACC_PUBLIC)
	ZEND_ME(PhpSdl_Window, close, arginfo_class_PhpSdl_Window_close, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static const zend_function_entry class_PhpSdl_Sdl_methods[] = {
	ZEND_ME(PhpSdl_Sdl, events, arginfo_class_PhpSdl_Sdl_events, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sdl, time, arginfo_class_PhpSdl_Sdl_time, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sdl, clipboard, arginfo_class_PhpSdl_Sdl_clipboard, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sdl, setClipboard, arginfo_class_PhpSdl_Sdl_setClipboard, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(PhpSdl_Sdl, version, arginfo_class_PhpSdl_Sdl_version, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static zend_class_entry *register_class_PhpSdl_Window(void)
{
	zend_class_entry ce, *class_entry;

	INIT_NS_CLASS_ENTRY(ce, "PhpSdl", "Window", class_PhpSdl_Window_methods);
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
