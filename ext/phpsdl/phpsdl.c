/*
 * phpsdl: myphp's native layer. SDL3 for the window, input and input methods;
 * OpenGL, NanoVG, textures, buffers and audio from PHP-GLFW's modules (gl/).
 *
 * Keys, modifiers and mouse buttons are reported in GLFW's numbering, and the
 * GLFW_KEY_* constants are defined, so code written for PHP-GLFW keeps working.
 */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>
#include <zend_API.h>
#include <zend_exceptions.h>
#include <ext/standard/info.h>

#include <glad/glad.h>
#include <SDL3/SDL.h>

#include "phpglfw.h"
#include "phpglfw_arginfo.h"
#include "phpglfw_constants.h"
#include "phpglfw_functions.h"
#include "phpglfw_math.h"
#include "phpglfw_buffer.h"
#include "phpglfw_texture.h"
#include "phpglfw_svgparser.h"
#include "phpglfw_vg.h"
#include "phpglfw_audio.h"

#include "php_phpsdl.h"
#include "phpsdl_arginfo.h"
#include "phpsdl_keys.h"
#include "render.h"

ZEND_DECLARE_MODULE_GLOBALS(phpsdl)

/* One OpenGL context, shared by every window and kept until the module shuts down:
 * NanoVG contexts and textures free their GPU objects when PHP releases them, which
 * may be after the window is gone. The first window, which made the context, stays
 * (hidden) until then too. */
static SDL_Window *phpsdl_first = NULL;
static SDL_GLContext phpsdl_context = NULL;

typedef struct {
    SDL_Window *window;
    zend_object std;
} phpsdl_window_object;

static zend_object_handlers phpsdl_window_handlers;

static inline phpsdl_window_object *phpsdl_window_from(zend_object *obj)
{
    return (phpsdl_window_object *) ((char *) obj - XtOffsetOf(phpsdl_window_object, std));
}

#define PHPSDL_WINDOW() (phpsdl_window_from(Z_OBJ_P(ZEND_THIS))->window)

static zend_object *phpsdl_window_create(zend_class_entry *ce)
{
    phpsdl_window_object *intern = zend_object_alloc(sizeof(phpsdl_window_object), ce);
    intern->window = NULL;
    zend_object_std_init(&intern->std, ce);
    object_properties_init(&intern->std, ce);
    intern->std.handlers = &phpsdl_window_handlers;
    return &intern->std;
}

static void phpsdl_window_free(zend_object *obj)
{
    phpsdl_window_object *intern = phpsdl_window_from(obj);
    if (intern->window) {
        if (intern->window == phpsdl_first) SDL_HideWindow(intern->window);
        else {
            if (SDL_GL_GetCurrentWindow() == intern->window) SDL_GL_MakeCurrent(phpsdl_first, phpsdl_context);
            SDL_DestroyWindow(intern->window);
        }
        intern->window = NULL;
    }
    zend_object_std_dtor(obj);
}
static SDL_Cursor *phpsdl_cursors[3] = {NULL, NULL, NULL};
static bool phpsdl_video = false;

static zend_class_entry *phpsdl_window_ce;
static zend_class_entry *phpsdl_sdl_ce;

static void phpsdl_throw(const char *what)
{
    zend_throw_exception_ex(zend_ce_exception, 0, "%s: %s", what, SDL_GetError());
}

/* GLFW key numbering, from the physical key (GLFW names keys by the US layout). */
static int phpsdl_glfw_key(SDL_Scancode sc)
{
    if (sc >= SDL_SCANCODE_A && sc <= SDL_SCANCODE_Z) return 65 + (sc - SDL_SCANCODE_A);
    if (sc >= SDL_SCANCODE_1 && sc <= SDL_SCANCODE_9) return 49 + (sc - SDL_SCANCODE_1);
    if (sc >= SDL_SCANCODE_F1 && sc <= SDL_SCANCODE_F12) return 290 + (sc - SDL_SCANCODE_F1);
    if (sc >= SDL_SCANCODE_F13 && sc <= SDL_SCANCODE_F24) return 302 + (sc - SDL_SCANCODE_F13);
    if (sc >= SDL_SCANCODE_KP_1 && sc <= SDL_SCANCODE_KP_9) return 321 + (sc - SDL_SCANCODE_KP_1);
    switch (sc) {
        case SDL_SCANCODE_0: return 48;
        case SDL_SCANCODE_SPACE: return 32;
        case SDL_SCANCODE_APOSTROPHE: return 39;
        case SDL_SCANCODE_COMMA: return 44;
        case SDL_SCANCODE_MINUS: return 45;
        case SDL_SCANCODE_PERIOD: return 46;
        case SDL_SCANCODE_SLASH: return 47;
        case SDL_SCANCODE_SEMICOLON: return 59;
        case SDL_SCANCODE_EQUALS: return 61;
        case SDL_SCANCODE_LEFTBRACKET: return 91;
        case SDL_SCANCODE_BACKSLASH: return 92;
        case SDL_SCANCODE_RIGHTBRACKET: return 93;
        case SDL_SCANCODE_GRAVE: return 96;
        case SDL_SCANCODE_NONUSBACKSLASH: return 162;
        case SDL_SCANCODE_ESCAPE: return 256;
        case SDL_SCANCODE_RETURN: return 257;
        case SDL_SCANCODE_TAB: return 258;
        case SDL_SCANCODE_BACKSPACE: return 259;
        case SDL_SCANCODE_INSERT: return 260;
        case SDL_SCANCODE_DELETE: return 261;
        case SDL_SCANCODE_RIGHT: return 262;
        case SDL_SCANCODE_LEFT: return 263;
        case SDL_SCANCODE_DOWN: return 264;
        case SDL_SCANCODE_UP: return 265;
        case SDL_SCANCODE_PAGEUP: return 266;
        case SDL_SCANCODE_PAGEDOWN: return 267;
        case SDL_SCANCODE_HOME: return 268;
        case SDL_SCANCODE_END: return 269;
        case SDL_SCANCODE_CAPSLOCK: return 280;
        case SDL_SCANCODE_SCROLLLOCK: return 281;
        case SDL_SCANCODE_NUMLOCKCLEAR: return 282;
        case SDL_SCANCODE_PRINTSCREEN: return 283;
        case SDL_SCANCODE_PAUSE: return 284;
        case SDL_SCANCODE_KP_0: return 320;
        case SDL_SCANCODE_KP_PERIOD: return 330;
        case SDL_SCANCODE_KP_DIVIDE: return 331;
        case SDL_SCANCODE_KP_MULTIPLY: return 332;
        case SDL_SCANCODE_KP_MINUS: return 333;
        case SDL_SCANCODE_KP_PLUS: return 334;
        case SDL_SCANCODE_KP_ENTER: return 335;
        case SDL_SCANCODE_KP_EQUALS: return 336;
        case SDL_SCANCODE_LSHIFT: return 340;
        case SDL_SCANCODE_LCTRL: return 341;
        case SDL_SCANCODE_LALT: return 342;
        case SDL_SCANCODE_LGUI: return 343;
        case SDL_SCANCODE_RSHIFT: return 344;
        case SDL_SCANCODE_RCTRL: return 345;
        case SDL_SCANCODE_RALT: return 346;
        case SDL_SCANCODE_RGUI: return 347;
        case SDL_SCANCODE_APPLICATION: return 348;
        default: return -1;
    }
}

static int phpsdl_glfw_mods(SDL_Keymod mod)
{
    int m = 0;
    if (mod & SDL_KMOD_SHIFT) m |= 0x0001;
    if (mod & SDL_KMOD_CTRL) m |= 0x0002;
    if (mod & SDL_KMOD_ALT) m |= 0x0004;
    if (mod & SDL_KMOD_GUI) m |= 0x0008;
    if (mod & SDL_KMOD_CAPS) m |= 0x0010;
    if (mod & SDL_KMOD_NUM) m |= 0x0020;
    return m;
}

static int phpsdl_glfw_button(Uint8 button)
{
    switch (button) {
        case SDL_BUTTON_LEFT: return 0;
        case SDL_BUTTON_RIGHT: return 1;
        case SDL_BUTTON_MIDDLE: return 2;
        default: return button - 1;
    }
}

static bool phpsdl_ensure_video(void)
{
    if (phpsdl_video) return true;
    /* The toolkit draws the text being composed; the input method shows its candidate list. */
    SDL_SetHint(SDL_HINT_IME_IMPLEMENTED_UI, "composition");
    /* A desktop app, not a game: let the screen saver run. */
    SDL_SetHint(SDL_HINT_VIDEO_ALLOW_SCREENSAVER, "1");
    if (!SDL_Init(SDL_INIT_VIDEO)) {
        phpsdl_throw("SDL could not start");
        return false;
    }
    phpsdl_video = true;
    return true;
}


/* Window ------------------------------------------------------------------- */

ZEND_METHOD(PhpSdl_Window, __construct)
{
    zend_string *title;
    zend_long width, height;
    ZEND_PARSE_PARAMETERS_START(3, 3)
        Z_PARAM_STR(title)
        Z_PARAM_LONG(width)
        Z_PARAM_LONG(height)
    ZEND_PARSE_PARAMETERS_END();

    if (!phpsdl_ensure_video()) RETURN_THROWS();

    SDL_GL_SetAttribute(SDL_GL_CONTEXT_MAJOR_VERSION, 3);
    SDL_GL_SetAttribute(SDL_GL_CONTEXT_MINOR_VERSION, 3);
    SDL_GL_SetAttribute(SDL_GL_CONTEXT_PROFILE_MASK, SDL_GL_CONTEXT_PROFILE_CORE);
    SDL_GL_SetAttribute(SDL_GL_CONTEXT_FLAGS, SDL_GL_CONTEXT_FORWARD_COMPATIBLE_FLAG);
    SDL_GL_SetAttribute(SDL_GL_STENCIL_SIZE, 8);
    SDL_GL_SetAttribute(SDL_GL_DOUBLEBUFFER, 1);

    SDL_Window *window = SDL_CreateWindow(ZSTR_VAL(title), (int) width, (int) height,
        SDL_WINDOW_OPENGL | SDL_WINDOW_RESIZABLE | SDL_WINDOW_HIGH_PIXEL_DENSITY);
    if (!window) {
        phpsdl_throw("SDL could not create the window");
        RETURN_THROWS();
    }
    if (!phpsdl_context) {
        phpsdl_context = SDL_GL_CreateContext(window);
        if (!phpsdl_context) {
            SDL_DestroyWindow(window);
            phpsdl_throw("SDL could not create an OpenGL 3.3 context");
            RETURN_THROWS();
        }
        phpsdl_first = window;
        SDL_GL_MakeCurrent(window, phpsdl_context);
        if (!gladLoadGLLoader((GLADloadproc) SDL_GL_GetProcAddress)) {
            zend_throw_error(NULL, "Could not load the OpenGL functions");
            RETURN_THROWS();
        }
        phpsdl_cursors[0] = SDL_CreateSystemCursor(SDL_SYSTEM_CURSOR_DEFAULT);
        phpsdl_cursors[1] = SDL_CreateSystemCursor(SDL_SYSTEM_CURSOR_POINTER);
        phpsdl_cursors[2] = SDL_CreateSystemCursor(SDL_SYSTEM_CURSOR_TEXT);
    }
    /* The newest window draws: one at a time, as the toolkit opens them. */
    SDL_GL_MakeCurrent(window, phpsdl_context);
    SDL_GL_SetSwapInterval(1);
    phpsdl_window_from(Z_OBJ_P(ZEND_THIS))->window = window;
    /* Text arrives only while text input is on; the toolkit turns it on for inputs. */
    SDL_StopTextInput(window);
}

ZEND_METHOD(PhpSdl_Window, size)
{
    ZEND_PARSE_PARAMETERS_NONE();
    int w = 0, h = 0;
    if (PHPSDL_WINDOW()) SDL_GetWindowSize(PHPSDL_WINDOW(), &w, &h);
    array_init(return_value);
    add_next_index_long(return_value, w);
    add_next_index_long(return_value, h);
}

ZEND_METHOD(PhpSdl_Window, pixelSize)
{
    ZEND_PARSE_PARAMETERS_NONE();
    int w = 0, h = 0;
    if (PHPSDL_WINDOW()) SDL_GetWindowSizeInPixels(PHPSDL_WINDOW(), &w, &h);
    array_init(return_value);
    add_next_index_long(return_value, w);
    add_next_index_long(return_value, h);
}

ZEND_METHOD(PhpSdl_Window, setSize)
{
    zend_long width, height;
    ZEND_PARSE_PARAMETERS_START(2, 2)
        Z_PARAM_LONG(width)
        Z_PARAM_LONG(height)
    ZEND_PARSE_PARAMETERS_END();
    SDL_Window *window = PHPSDL_WINDOW();
    if (!window) return;
    SDL_SetWindowSize(window, (int) width, (int) height);
    SDL_SyncWindow(window);
}

ZEND_METHOD(PhpSdl_Window, setTitle)
{
    zend_string *title;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_STR(title)
    ZEND_PARSE_PARAMETERS_END();
    if (PHPSDL_WINDOW()) SDL_SetWindowTitle(PHPSDL_WINDOW(), ZSTR_VAL(title));
}

ZEND_METHOD(PhpSdl_Window, swap)
{
    ZEND_PARSE_PARAMETERS_NONE();
    if (PHPSDL_WINDOW()) SDL_GL_SwapWindow(PHPSDL_WINDOW());
}

ZEND_METHOD(PhpSdl_Window, raise)
{
    ZEND_PARSE_PARAMETERS_NONE();
    if (PHPSDL_WINDOW()) SDL_RaiseWindow(PHPSDL_WINDOW());
}

ZEND_METHOD(PhpSdl_Window, focused)
{
    ZEND_PARSE_PARAMETERS_NONE();
    RETURN_BOOL(PHPSDL_WINDOW() && (SDL_GetWindowFlags(PHPSDL_WINDOW()) & SDL_WINDOW_INPUT_FOCUS));
}

ZEND_METHOD(PhpSdl_Window, setCursor)
{
    zend_string *name;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_STR(name)
    ZEND_PARSE_PARAMETERS_END();
    int i = zend_string_equals_literal(name, "pointer") ? 1 : zend_string_equals_literal(name, "text") ? 2 : 0;
    if (phpsdl_cursors[i]) SDL_SetCursor(phpsdl_cursors[i]);
}

ZEND_METHOD(PhpSdl_Window, startTextInput)
{
    zend_long x, y, w, h;
    ZEND_PARSE_PARAMETERS_START(4, 4)
        Z_PARAM_LONG(x)
        Z_PARAM_LONG(y)
        Z_PARAM_LONG(w)
        Z_PARAM_LONG(h)
    ZEND_PARSE_PARAMETERS_END();
    SDL_Window *window = PHPSDL_WINDOW();
    if (!window) return;
    SDL_Rect rect = {(int) x, (int) y, (int) (w > 0 ? w : 1), (int) (h > 0 ? h : 1)};
    SDL_SetTextInputArea(window, &rect, 0);
    if (!SDL_TextInputActive(window)) SDL_StartTextInput(window);
}

ZEND_METHOD(PhpSdl_Window, stopTextInput)
{
    ZEND_PARSE_PARAMETERS_NONE();
    if (PHPSDL_WINDOW() && SDL_TextInputActive(PHPSDL_WINDOW())) SDL_StopTextInput(PHPSDL_WINDOW());
}

ZEND_METHOD(PhpSdl_Window, vsync)
{
    bool on;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_BOOL(on)
    ZEND_PARSE_PARAMETERS_END();
    if (PHPSDL_WINDOW()) {
        SDL_GL_MakeCurrent(PHPSDL_WINDOW(), phpsdl_context);
        SDL_GL_SetSwapInterval(on ? 1 : 0);
    }
}

ZEND_METHOD(PhpSdl_Window, close)
{
    ZEND_PARSE_PARAMETERS_NONE();
    if (PHPSDL_WINDOW()) SDL_HideWindow(PHPSDL_WINDOW());
}

/* Sdl ------------------------------------------------------------------------ */

static void phpsdl_add_event(zval *list, SDL_Event *ev)
{
    zval e;
    switch (ev->type) {
        case SDL_EVENT_QUIT:
        case SDL_EVENT_WINDOW_CLOSE_REQUESTED:
            array_init(&e);
            add_assoc_string(&e, "type", "quit");
            break;
        case SDL_EVENT_MOUSE_MOTION:
            array_init(&e);
            add_assoc_string(&e, "type", "pointer");
            add_assoc_double(&e, "x", ev->motion.x);
            add_assoc_double(&e, "y", ev->motion.y);
            break;
        case SDL_EVENT_MOUSE_BUTTON_DOWN:
        case SDL_EVENT_MOUSE_BUTTON_UP:
            array_init(&e);
            add_assoc_string(&e, "type", "button");
            add_assoc_long(&e, "button", phpsdl_glfw_button(ev->button.button));
            add_assoc_bool(&e, "pressed", ev->type == SDL_EVENT_MOUSE_BUTTON_DOWN);
            add_assoc_double(&e, "x", ev->button.x);
            add_assoc_double(&e, "y", ev->button.y);
            break;
        case SDL_EVENT_MOUSE_WHEEL: {
            float dy = ev->wheel.y;
            if (ev->wheel.direction == SDL_MOUSEWHEEL_FLIPPED) dy = -dy;
            array_init(&e);
            add_assoc_string(&e, "type", "scroll");
            add_assoc_double(&e, "dx", ev->wheel.x);
            add_assoc_double(&e, "dy", dy);
            break;
        }
        case SDL_EVENT_TEXT_INPUT:
            array_init(&e);
            add_assoc_string(&e, "type", "text");
            add_assoc_string(&e, "text", (char *) ev->text.text);
            break;
        case SDL_EVENT_TEXT_EDITING:
            array_init(&e);
            add_assoc_string(&e, "type", "preedit");
            add_assoc_string(&e, "text", (char *) (ev->edit.text ? ev->edit.text : ""));
            add_assoc_long(&e, "caret", ev->edit.start);
            add_assoc_long(&e, "length", ev->edit.length);
            break;
        case SDL_EVENT_KEY_DOWN: {
            int key = phpsdl_glfw_key(ev->key.scancode);
            if (key < 0) return;
            array_init(&e);
            add_assoc_string(&e, "type", "key");
            add_assoc_long(&e, "key", key);
            add_assoc_long(&e, "mods", phpsdl_glfw_mods(ev->key.mod));
            add_assoc_bool(&e, "repeat", ev->key.repeat);
            break;
        }
        case SDL_EVENT_DROP_FILE:
            array_init(&e);
            add_assoc_string(&e, "type", "drop");
            add_assoc_string(&e, "path", (char *) (ev->drop.data ? ev->drop.data : ""));
            add_assoc_double(&e, "x", ev->drop.x);
            add_assoc_double(&e, "y", ev->drop.y);
            break;
        case SDL_EVENT_WINDOW_EXPOSED:
        case SDL_EVENT_WINDOW_RESIZED:
        case SDL_EVENT_WINDOW_PIXEL_SIZE_CHANGED:
        case SDL_EVENT_WINDOW_DISPLAY_SCALE_CHANGED:
            array_init(&e);
            add_assoc_string(&e, "type", "refresh");
            break;
        case SDL_EVENT_WINDOW_FOCUS_GAINED:
        case SDL_EVENT_WINDOW_FOCUS_LOST:
            array_init(&e);
            add_assoc_string(&e, "type", "focus");
            add_assoc_bool(&e, "focused", ev->type == SDL_EVENT_WINDOW_FOCUS_GAINED);
            break;
        default:
            return;
    }
    add_next_index_zval(list, &e);
}

ZEND_METHOD(PhpSdl_Sdl, events)
{
    double timeout;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_DOUBLE(timeout)
    ZEND_PARSE_PARAMETERS_END();
    array_init(return_value);
    if (!phpsdl_video) return;
    SDL_Event ev;
    Sint32 ms = timeout <= 0 ? 0 : (Sint32) (timeout * 1000.0 + 0.5);
    if (ms <= 0 ? SDL_PollEvent(&ev) : SDL_WaitEventTimeout(&ev, ms)) {
        phpsdl_add_event(return_value, &ev);
        while (SDL_PollEvent(&ev)) phpsdl_add_event(return_value, &ev);
    }
}

ZEND_METHOD(PhpSdl_Sdl, time)
{
    ZEND_PARSE_PARAMETERS_NONE();
    RETURN_DOUBLE((double) SDL_GetTicksNS() / 1e9);
}

ZEND_METHOD(PhpSdl_Sdl, clipboard)
{
    ZEND_PARSE_PARAMETERS_NONE();
    if (!phpsdl_video) RETURN_EMPTY_STRING();
    char *text = SDL_GetClipboardText();
    if (!text) RETURN_EMPTY_STRING();
    RETVAL_STRING(text);
    SDL_free(text);
}

ZEND_METHOD(PhpSdl_Sdl, setClipboard)
{
    zend_string *text;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_STR(text)
    ZEND_PARSE_PARAMETERS_END();
    if (!phpsdl_ensure_video()) RETURN_THROWS();
    SDL_SetClipboardText(ZSTR_VAL(text));
}

ZEND_METHOD(PhpSdl_Sdl, version)
{
    ZEND_PARSE_PARAMETERS_NONE();
    int v = SDL_GetVersion();
    RETURN_STR(zend_strpprintf(0, "%d.%d.%d", SDL_VERSIONNUM_MAJOR(v), SDL_VERSIONNUM_MINOR(v), SDL_VERSIONNUM_MICRO(v)));
}

/* Module ------------------------------------------------------------------- */

PHP_INI_BEGIN()
    STD_PHP_INI_BOOLEAN("phpsdl.buffer_serialize_hex_float", "0", PHP_INI_ALL, OnUpdateBool, buffer_serialize_hex_float, zend_phpsdl_globals, phpsdl_globals)
PHP_INI_END()

PHP_MINIT_FUNCTION(phpsdl)
{
    REGISTER_INI_ENTRIES();
    phpglfw_register_constants(INIT_FUNC_ARGS_PASSTHRU);
    phpglfw_init_callbacks();
    phpglfw_register_ipos();
    phpglfw_register_math_module(INIT_FUNC_ARGS_PASSTHRU);
    phpglfw_register_buffer_module(INIT_FUNC_ARGS_PASSTHRU);
    phpglfw_register_texture_module(INIT_FUNC_ARGS_PASSTHRU);
    phpglfw_register_svgparser_module(INIT_FUNC_ARGS_PASSTHRU);
    phpglfw_register_vg_module(INIT_FUNC_ARGS_PASSTHRU);
    phpglfw_register_audio_module(INIT_FUNC_ARGS_PASSTHRU);
    for (int i = 0; phpsdl_glfw_constants[i].name; i++) {
        zend_register_long_constant(phpsdl_glfw_constants[i].name, strlen(phpsdl_glfw_constants[i].name),
            phpsdl_glfw_constants[i].value, CONST_PERSISTENT, module_number);
    }
    phpsdl_window_ce = register_class_PhpSdl_Window();
    phpsdl_window_ce->create_object = phpsdl_window_create;
    memcpy(&phpsdl_window_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
    phpsdl_window_handlers.offset = XtOffsetOf(phpsdl_window_object, std);
    phpsdl_window_handlers.free_obj = phpsdl_window_free;
    phpsdl_window_handlers.clone_obj = NULL;
    phpsdl_sdl_ce = register_class_PhpSdl_Sdl();
    register_class_PhpSdl_Text();
    phpsdl_render_register(register_class_PhpSdl_Renderer());
    return SUCCESS;
}

PHP_MSHUTDOWN_FUNCTION(phpsdl)
{
    UNREGISTER_INI_ENTRIES();
    phpglfw_shutdown_callbacks();
    phpsdl_render_shutdown();
    for (int i = 0; i < 3; i++) if (phpsdl_cursors[i]) SDL_DestroyCursor(phpsdl_cursors[i]);
    if (phpsdl_context) SDL_GL_DestroyContext(phpsdl_context);
    if (phpsdl_first) SDL_DestroyWindow(phpsdl_first);
    if (phpsdl_video) SDL_Quit();
    phpsdl_first = NULL;
    phpsdl_context = NULL;
    phpsdl_video = false;
    return SUCCESS;
}

PHP_MINFO_FUNCTION(phpsdl)
{
    char sdl[32];
    int v = SDL_GetVersion();
    snprintf(sdl, sizeof sdl, "%d.%d.%d", SDL_VERSIONNUM_MAJOR(v), SDL_VERSIONNUM_MINOR(v), SDL_VERSIONNUM_MICRO(v));
    php_info_print_table_start();
    php_info_print_table_row(2, "phpsdl support", "enabled");
    php_info_print_table_row(2, "phpsdl version", PHP_PHPSDL_VERSION);
    php_info_print_table_row(2, "SDL version", sdl);
    php_info_print_table_end();
    DISPLAY_INI_ENTRIES();
}

static PHP_GINIT_FUNCTION(phpsdl)
{
#if defined(COMPILE_DL_PHPSDL) && defined(ZTS)
    ZEND_TSRMLS_CACHE_UPDATE();
#endif
    phpsdl_globals->buffer_serialize_hex_float = false;
}

zend_module_entry phpsdl_module_entry = {
    STANDARD_MODULE_HEADER,
    "phpsdl",
    ext_functions,
    PHP_MINIT(phpsdl),
    PHP_MSHUTDOWN(phpsdl),
    NULL,
    NULL,
    PHP_MINFO(phpsdl),
    PHP_PHPSDL_VERSION,
    PHP_MODULE_GLOBALS(phpsdl),
    PHP_GINIT(phpsdl),
    NULL,
    NULL,
    STANDARD_MODULE_PROPERTIES_EX
};

#ifdef COMPILE_DL_PHPSDL
ZEND_GET_MODULE(phpsdl)
#endif
