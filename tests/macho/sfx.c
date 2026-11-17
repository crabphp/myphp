// A stand-in for the micro runtime: reserves __DATA,__micro_sfxsize like phpsdl does
// and prints the payload that section points at. tests/macho/embed.php puts one in.
#include <stdio.h>
#include <stdint.h>
#include <mach-o/getsect.h>
#include <mach-o/dyld.h>

__attribute__((used, section("__DATA,__micro_sfxsize"))) static const unsigned char sfxsize[16] = {0};

int main(void)
{
    unsigned long size = 0;
    const unsigned char *s = getsectiondata((const struct mach_header_64 *) _dyld_get_image_header(0), "__DATA", "__micro_sfxsize", &size);
    if (!s || size < 16) { puts("no section"); return 1; }
    unsigned long long start = 0, end = 0;
    for (int i = 0; i < 8; i++) { start = start << 8 | s[i]; end = end << 8 | s[8 + i]; }
    char path[4096];
    uint32_t n = sizeof path;
    _NSGetExecutablePath(path, &n);
    FILE *f = fopen(path, "rb");
    fseek(f, (long) start, SEEK_SET);
    char buf[256] = {0};
    size_t want = end - start < 255 ? end - start : 255;
    fread(buf, 1, want, f);
    printf("payload [%llu, %llu): %s\n", start, end, buf);
    return 0;
}
