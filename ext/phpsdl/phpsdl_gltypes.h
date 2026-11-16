#ifndef PHPSDL_GLTYPES_H
#define PHPSDL_GLTYPES_H

/* OpenGL's scalar types, which PHP-GLFW's buffers (GL\Buffer\FloatBuffer, ...) are
 * typed with. phpsdl has no OpenGL: these are only the C types. */
#include <stdint.h>
#include <stddef.h>

typedef unsigned int GLenum;
typedef unsigned char GLboolean;
typedef unsigned int GLbitfield;
typedef void GLvoid;
typedef int8_t GLbyte;
typedef uint8_t GLubyte;
typedef int16_t GLshort;
typedef uint16_t GLushort;
typedef int GLint;
typedef unsigned int GLuint;
typedef int GLsizei;
typedef uint16_t GLhalf;
typedef float GLfloat;
typedef double GLdouble;
typedef int64_t GLint64;
typedef uint64_t GLuint64;

#endif
