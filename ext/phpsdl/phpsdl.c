/*
 * phpsdl: myphp's native layer. SDL3 for the window, input and input methods; SDL_GPU
 * (Vulkan, Metal, Direct3D 12) for drawing, with text and canvas paths (render.c,
 * path.c, raster.c); sound with miniaudio (audio.c).
 *
 * Keys are numbered by their place on a US keyboard (letters and digits as ASCII,
 * others from 256), modifiers as bits and mouse buttons from 0: PhpUi\Key, Mod and
 * Mouse name them.
 */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>
#include <zend_API.h>
#include <zend_exceptions.h>
#include <ext/standard/info.h>

#include <SDL3/SDL.h>

#include "php_phpsdl.h"
#include "phpsdl_arginfo.h"
#include "render.h"
#include "audio.h"
#include "stb_image.h"

#ifdef __APPLE__
/* Where the micro runtime finds its app: [start, end) in the executable, big endian.
 * myphp writes it when it puts the app inside __LINKEDIT, where codesign can sign it
 * (cli/package.php, myphpMachoEmbed). */
__attribute__((used, section("__DATA,__micro_sfxsize"))) static const unsigned char phpsdl_micro_sfxsize[16] = {0};
#endif

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
    /* The GPU device, and what NanoVG and the renderer made on it, outlive windows. */
    if (intern->window) {
        phpsdl_gpu_release(intern->window);
        SDL_DestroyWindow(intern->window);
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

/* A key's number, from its place on the keyboard (named after the US layout). */
static int phpsdl_key(SDL_Scancode sc)
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

static int phpsdl_mods(SDL_Keymod mod)
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

static int phpsdl_button(Uint8 button)
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

static void phpsdl_create_cursors(void)
{
    if (phpsdl_cursors[0]) return;
    phpsdl_cursors[0] = SDL_CreateSystemCursor(SDL_SYSTEM_CURSOR_DEFAULT);
    phpsdl_cursors[1] = SDL_CreateSystemCursor(SDL_SYSTEM_CURSOR_POINTER);
    phpsdl_cursors[2] = SDL_CreateSystemCursor(SDL_SYSTEM_CURSOR_TEXT);
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

    SDL_Window *window = SDL_CreateWindow(ZSTR_VAL(title), (int) width, (int) height,
        SDL_WINDOW_RESIZABLE | SDL_WINDOW_HIGH_PIXEL_DENSITY);
    if (!window) {
        phpsdl_throw("SDL could not create the window");
        RETURN_THROWS();
    }
    if (!phpsdl_gpu_claim(window)) {
        SDL_DestroyWindow(window);
        phpsdl_throw("No GPU to draw with (phpsdl needs Vulkan, Metal or Direct3D 12)");
        RETURN_THROWS();
    }
    phpsdl_create_cursors();
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

static void phpsdl_read_color(HashTable *ht, float out[4])
{
    out[0] = out[1] = out[2] = 0; out[3] = 1;
    zval *v;
    int i = 0;
    ZEND_HASH_FOREACH_VAL(ht, v) {
        if (i >= 4) break;
        out[i++] = (float) zval_get_double(v);
    } ZEND_HASH_FOREACH_END();
}

ZEND_METHOD(PhpSdl_Window, begin)
{
    HashTable *clear;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_ARRAY_HT(clear)
    ZEND_PARSE_PARAMETERS_END();
    SDL_Window *window = PHPSDL_WINDOW();
    if (!window) RETURN_FALSE;
    float color[4];
    phpsdl_read_color(clear, color);
    int w = 0, h = 0;
    SDL_GetWindowSizeInPixels(window, &w, &h);
    RETURN_BOOL(phpsdl_frame_begin(window, w, h, color));
}

ZEND_METHOD(PhpSdl_Window, setIcon)
{
    zend_string *path;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_PATH_STR(path)
    ZEND_PARSE_PARAMETERS_END();
    SDL_Window *window = PHPSDL_WINDOW();
    if (!window) RETURN_FALSE;
    int w, h, n;
    unsigned char *px = stbi_load(ZSTR_VAL(path), &w, &h, &n, 4);
    if (!px) RETURN_FALSE;
    SDL_Surface *icon = SDL_CreateSurfaceFrom(w, h, SDL_PIXELFORMAT_RGBA32, px, w * 4);
    bool ok = icon && SDL_SetWindowIcon(window, icon);
    if (icon) SDL_DestroySurface(icon);
    stbi_image_free(px);
    RETURN_BOOL(ok);
}

ZEND_METHOD(PhpSdl_Window, swap)
{
    ZEND_PARSE_PARAMETERS_NONE();
    if (PHPSDL_WINDOW()) phpsdl_frame_end(PHPSDL_WINDOW(), NULL);
}

ZEND_METHOD(PhpSdl_Window, beginCapture)
{
    zend_long width, height;
    HashTable *clear;
    ZEND_PARSE_PARAMETERS_START(3, 3)
        Z_PARAM_LONG(width)
        Z_PARAM_LONG(height)
        Z_PARAM_ARRAY_HT(clear)
    ZEND_PARSE_PARAMETERS_END();
    float color[4];
    phpsdl_read_color(clear, color);
    if (!phpsdl_frame_begin(NULL, (int) width, (int) height, color)) {
        zend_throw_error(NULL, "Could not draw off screen at %dx%d", (int) width, (int) height);
    }
}

ZEND_METHOD(PhpSdl_Window, endCapture)
{
    zend_string *path;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_PATH_STR(path)
    ZEND_PARSE_PARAMETERS_END();
    if (!phpsdl_frame_end(NULL, ZSTR_VAL(path))) zend_throw_error(NULL, "Could not save %s", ZSTR_VAL(path));
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
    if (PHPSDL_WINDOW()) phpsdl_gpu_vsync(PHPSDL_WINDOW(), on);
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
            add_assoc_long(&e, "button", phpsdl_button(ev->button.button));
            add_assoc_bool(&e, "pressed", ev->type == SDL_EVENT_MOUSE_BUTTON_DOWN);
            add_assoc_double(&e, "x", ev->button.x);
            add_assoc_double(&e, "y", ev->button.y);
            break;
        case SDL_EVENT_MOUSE_WHEEL: {
            /* As the system scrolls: SDL's values already follow "natural" scrolling (its
             * FLIPPED flag only says so), as other apps scroll. */
            array_init(&e);
            add_assoc_string(&e, "type", "scroll");
            add_assoc_double(&e, "dx", ev->wheel.x);
            add_assoc_double(&e, "dy", ev->wheel.y);
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
            int key = phpsdl_key(ev->key.scancode);
            if (key < 0) return;
            array_init(&e);
            add_assoc_string(&e, "type", "key");
            add_assoc_long(&e, "key", key);
            add_assoc_long(&e, "mods", phpsdl_mods(ev->key.mod));
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

ZEND_METHOD(PhpSdl_Sdl, backend)
{
    ZEND_PARSE_PARAMETERS_NONE();
    RETURN_STRING(phpsdl_backend());
}

ZEND_METHOD(PhpSdl_Sdl, version)
{
    ZEND_PARSE_PARAMETERS_NONE();
    int v = SDL_GetVersion();
    RETURN_STR(zend_strpprintf(0, "%d.%d.%d", SDL_VERSIONNUM_MAJOR(v), SDL_VERSIONNUM_MINOR(v), SDL_VERSIONNUM_MICRO(v)));
}

/* Module ------------------------------------------------------------------- */

PHP_MINIT_FUNCTION(phpsdl)
{
    phpsdl_window_ce = register_class_PhpSdl_Window();
    phpsdl_window_ce->create_object = phpsdl_window_create;
    memcpy(&phpsdl_window_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
    phpsdl_window_handlers.offset = XtOffsetOf(phpsdl_window_object, std);
    phpsdl_window_handlers.free_obj = phpsdl_window_free;
    phpsdl_window_handlers.clone_obj = NULL;
    phpsdl_sdl_ce = register_class_PhpSdl_Sdl();

    register_class_PhpSdl_Text();
    zend_class_entry *renderer_ce = register_class_PhpSdl_Renderer();
    phpsdl_render_register(renderer_ce, register_class_PhpSdl_Path());
    phpsdl_audio_register(register_class_PhpSdl_Sound());
    return SUCCESS;
}

PHP_MSHUTDOWN_FUNCTION(phpsdl)
{
    phpsdl_audio_shutdown();
    phpsdl_render_shutdown();
    for (int i = 0; i < 3; i++) if (phpsdl_cursors[i]) SDL_DestroyCursor(phpsdl_cursors[i]);
    if (phpsdl_video) SDL_Quit();
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
}

zend_module_entry phpsdl_module_entry = {
    STANDARD_MODULE_HEADER,
    "phpsdl",
    NULL,
    PHP_MINIT(phpsdl),
    PHP_MSHUTDOWN(phpsdl),
    NULL,
    NULL,
    PHP_MINFO(phpsdl),
    PHP_PHPSDL_VERSION,
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_PHPSDL
ZEND_GET_MODULE(phpsdl)
#endif
