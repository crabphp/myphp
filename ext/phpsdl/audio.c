/*
 * PhpSdl\Sound: playing MP3, FLAC and WAV files with miniaudio, through one engine
 * started on first use; and reading a file's length and loudness (for waveforms)
 * without playing it.
 */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>
#include <zend_exceptions.h>
#include <ext/spl/spl_exceptions.h>
#include <math.h>

#define MINIAUDIO_IMPLEMENTATION
#define MA_NO_GENERATION
#include "miniaudio.h"

#include "audio.h"

static ma_engine *engine = NULL;

typedef struct {
    ma_sound *sound;
    zend_object std;
} pa_sound_object;

static zend_object_handlers sound_handlers;
static zend_class_entry *sound_ce;

static inline pa_sound_object *sound_from(zend_object *obj)
{
    return (pa_sound_object *) ((char *) obj - XtOffsetOf(pa_sound_object, std));
}

static ma_sound *this_sound(zval *self)
{
    ma_sound *s = sound_from(Z_OBJ_P(self))->sound;
    if (!s) zend_throw_error(NULL, "The sound was not loaded");
    return s;
}

static zend_object *sound_create(zend_class_entry *ce)
{
    pa_sound_object *o = zend_object_alloc(sizeof(pa_sound_object), ce);
    o->sound = NULL;
    zend_object_std_init(&o->std, ce);
    object_properties_init(&o->std, ce);
    o->std.handlers = &sound_handlers;
    return &o->std;
}

static void sound_free(zend_object *obj)
{
    pa_sound_object *o = sound_from(obj);
    if (o->sound) {
        ma_sound_uninit(o->sound);
        free(o->sound);
        o->sound = NULL;
    }
    zend_object_std_dtor(obj);
}

/* The engine on the default output device, started the first time a sound loads. */
static bool engine_start(void)
{
    if (engine) return true;
    engine = malloc(sizeof(ma_engine));
    if (!engine) return false;
    ma_result r = ma_engine_init(NULL, engine);
    if (r != MA_SUCCESS) {
        free(engine);
        engine = NULL;
        zend_throw_exception_ex(spl_ce_RuntimeException, 0, "No audio device: %s", ma_result_description(r));
        return false;
    }
    return true;
}

PHP_METHOD(PhpSdl_Sound, __construct)
{
    zend_string *path;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_PATH_STR(path)
    ZEND_PARSE_PARAMETERS_END();
    if (!engine_start()) RETURN_THROWS();
    pa_sound_object *o = sound_from(Z_OBJ_P(ZEND_THIS));
    if (o->sound) { zend_throw_error(NULL, "The sound is already loaded"); RETURN_THROWS(); }
    ma_sound *s = malloc(sizeof(ma_sound));
    /* Streamed: long files play without being decoded into memory first. */
    ma_result r = s ? ma_sound_init_from_file(engine, ZSTR_VAL(path), MA_SOUND_FLAG_STREAM, NULL, NULL, s) : MA_OUT_OF_MEMORY;
    if (r != MA_SUCCESS) {
        free(s);
        zend_throw_exception_ex(spl_ce_RuntimeException, 0, "Cannot play %s: %s", ZSTR_VAL(path), ma_result_description(r));
        RETURN_THROWS();
    }
    o->sound = s;
}

PHP_METHOD(PhpSdl_Sound, play)
{
    ZEND_PARSE_PARAMETERS_NONE();
    ma_sound *s = this_sound(ZEND_THIS);
    if (s) ma_sound_start(s);
}

PHP_METHOD(PhpSdl_Sound, pause)
{
    ZEND_PARSE_PARAMETERS_NONE();
    ma_sound *s = this_sound(ZEND_THIS);
    if (s) ma_sound_stop(s);
}

PHP_METHOD(PhpSdl_Sound, seek)
{
    double seconds;
    ZEND_PARSE_PARAMETERS_START(1, 1) Z_PARAM_DOUBLE(seconds) ZEND_PARSE_PARAMETERS_END();
    ma_sound *s = this_sound(ZEND_THIS);
    if (!s) RETURN_THROWS();
    ma_uint32 rate = 0;
    ma_sound_get_data_format(s, NULL, NULL, &rate, NULL, 0);
    if (rate == 0) rate = ma_engine_get_sample_rate(engine);
    ma_sound_seek_to_pcm_frame(s, (ma_uint64) (fmax(0, seconds) * rate));
}

PHP_METHOD(PhpSdl_Sound, position)
{
    ZEND_PARSE_PARAMETERS_NONE();
    ma_sound *s = this_sound(ZEND_THIS);
    if (!s) RETURN_THROWS();
    float t = 0;
    ma_sound_get_cursor_in_seconds(s, &t);
    RETURN_DOUBLE(t);
}

PHP_METHOD(PhpSdl_Sound, duration)
{
    ZEND_PARSE_PARAMETERS_NONE();
    ma_sound *s = this_sound(ZEND_THIS);
    if (!s) RETURN_THROWS();
    float t = 0;
    ma_sound_get_length_in_seconds(s, &t);
    RETURN_DOUBLE(t);
}

PHP_METHOD(PhpSdl_Sound, playing)
{
    ZEND_PARSE_PARAMETERS_NONE();
    ma_sound *s = this_sound(ZEND_THIS);
    if (!s) RETURN_THROWS();
    RETURN_BOOL(ma_sound_is_playing(s));
}

PHP_METHOD(PhpSdl_Sound, ended)
{
    ZEND_PARSE_PARAMETERS_NONE();
    ma_sound *s = this_sound(ZEND_THIS);
    if (!s) RETURN_THROWS();
    RETURN_BOOL(ma_sound_at_end(s));
}

PHP_METHOD(PhpSdl_Sound, volume)
{
    double volume;
    ZEND_PARSE_PARAMETERS_START(1, 1) Z_PARAM_DOUBLE(volume) ZEND_PARSE_PARAMETERS_END();
    ma_sound *s = this_sound(ZEND_THIS);
    if (s) ma_sound_set_volume(s, (float) fmax(0, volume));
}

PHP_METHOD(PhpSdl_Sound, loop)
{
    bool on = true;
    ZEND_PARSE_PARAMETERS_START(0, 1) Z_PARAM_OPTIONAL Z_PARAM_BOOL(on) ZEND_PARSE_PARAMETERS_END();
    ma_sound *s = this_sound(ZEND_THIS);
    if (s) ma_sound_set_looping(s, on);
}

/* Whether there is an audio device to play on (starting the engine if needed). */
PHP_METHOD(PhpSdl_Sound, available)
{
    ZEND_PARSE_PARAMETERS_NONE();
    bool ok = engine_start();
    if (EG(exception)) zend_clear_exception();
    RETURN_BOOL(ok);
}

/* The volume of everything played, 0 to 1 (more amplifies). */
PHP_METHOD(PhpSdl_Sound, masterVolume)
{
    double volume;
    ZEND_PARSE_PARAMETERS_START(1, 1) Z_PARAM_DOUBLE(volume) ZEND_PARSE_PARAMETERS_END();
    if (!engine_start()) RETURN_THROWS();
    ma_engine_set_volume(engine, (float) fmax(0, volume));
}

/* A decoder for $path as 32-bit float, at the file's own rate and channels. */
static bool open_decoder(zend_string *path, ma_decoder *d)
{
    ma_decoder_config config = ma_decoder_config_init(ma_format_f32, 0, 0);
    ma_result r = ma_decoder_init_file(ZSTR_VAL(path), &config, d);
    if (r != MA_SUCCESS) {
        zend_throw_exception_ex(spl_ce_RuntimeException, 0, "Cannot read %s: %s", ZSTR_VAL(path), ma_result_description(r));
        return false;
    }
    return true;
}

PHP_METHOD(PhpSdl_Sound, length)
{
    zend_string *path;
    ZEND_PARSE_PARAMETERS_START(1, 1) Z_PARAM_PATH_STR(path) ZEND_PARSE_PARAMETERS_END();
    ma_decoder d;
    if (!open_decoder(path, &d)) RETURN_THROWS();
    ma_uint64 frames = 0;
    ma_decoder_get_length_in_pcm_frames(&d, &frames);
    double seconds = d.outputSampleRate ? (double) frames / d.outputSampleRate : 0;
    ma_decoder_uninit(&d);
    RETURN_DOUBLE(seconds);
}

/* The loudest sample in each of $count equal slices of the file, scaled so the loudest
 * slice is 1: a waveform. */
PHP_METHOD(PhpSdl_Sound, peaks)
{
    zend_string *path;
    zend_long count;
    ZEND_PARSE_PARAMETERS_START(2, 2) Z_PARAM_PATH_STR(path) Z_PARAM_LONG(count) ZEND_PARSE_PARAMETERS_END();
    if (count < 1 || count > 100000) { zend_value_error("The count is out of range"); RETURN_THROWS(); }
    ma_decoder d;
    if (!open_decoder(path, &d)) RETURN_THROWS();
    ma_uint64 total = 0;
    ma_decoder_get_length_in_pcm_frames(&d, &total);
    ma_uint32 channels = d.outputChannels ? d.outputChannels : 1;
    float *peaks = ecalloc((size_t) count, sizeof(float));
    float chunk[4096];
    ma_uint64 per = total / (ma_uint64) count;
    if (per == 0) per = 1;
    ma_uint64 frame = 0, read;
    ma_uint64 want = sizeof chunk / sizeof(float) / channels;
    while (ma_decoder_read_pcm_frames(&d, chunk, want, &read) == MA_SUCCESS && read > 0) {
        for (ma_uint64 f = 0; f < read; f++, frame++) {
            zend_long slice = (zend_long) (frame / per);
            if (slice >= count) slice = count - 1;
            for (ma_uint32 c = 0; c < channels; c++) {
                float v = fabsf(chunk[f * channels + c]);
                if (v > peaks[slice]) peaks[slice] = v;
            }
        }
    }
    ma_decoder_uninit(&d);
    float top = 0.001f;
    for (zend_long i = 0; i < count; i++) if (peaks[i] > top) top = peaks[i];
    array_init_size(return_value, (uint32_t) count);
    for (zend_long i = 0; i < count; i++) add_next_index_double(return_value, peaks[i] / top);
    efree(peaks);
}

void phpsdl_audio_register(zend_class_entry *ce)
{
    sound_ce = ce;
    ce->create_object = sound_create;
    memcpy(&sound_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
    sound_handlers.offset = XtOffsetOf(pa_sound_object, std);
    sound_handlers.free_obj = sound_free;
    sound_handlers.clone_obj = NULL;
}

void phpsdl_audio_shutdown(void)
{
    if (engine) {
        ma_engine_uninit(engine);
        free(engine);
        engine = NULL;
    }
}
