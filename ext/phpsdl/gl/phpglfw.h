/*
 * phpsdl: the PHP-GLFW modules in gl/ include this header for their module globals.
 * They now belong to phpsdl, which has no GLFW.
 */
#ifndef PHP_GLFW_H
#define PHP_GLFW_H 1

#include "php.h"

ZEND_BEGIN_MODULE_GLOBALS(phpsdl)
    bool buffer_serialize_hex_float;
ZEND_END_MODULE_GLOBALS(phpsdl)

ZEND_EXTERN_MODULE_GLOBALS(phpsdl)

#ifdef ZTS
#define PHPGLFW_G(v) TSRMG(phpsdl_globals_id, zend_phpsdl_globals *, v)
#else
#define PHPGLFW_G(v) (phpsdl_globals.v)
#endif

#endif
