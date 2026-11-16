<?php

declare(strict_types=1);

namespace PhpUi;

/**
 * A window on phpsdl (SDL3, ext/phpsdl): input methods show the text being composed
 * at the caret and place their candidate list beside it. phpsdl delivers key codes
 * and modifiers in GLFW numbering, so the toolkit and apps keep GLFW_KEY_*.
 */
final class SdlWindow extends Window
{
    public readonly \PhpSdl\Window $handle;
    private bool $closing = false;
    private ?array $inputRect = null;

    public function __construct(string $title, int $width, int $height)
    {
        $this->handle = new \PhpSdl\Window($title, $width, $height);
    }

    public function size(): array { return $this->handle->size(); }
    public function framebufferSize(): array { return $this->handle->pixelSize(); }
    public function setSize(int $width, int $height): void { $this->handle->setSize($width, $height); }
    public function setTitle(string $title): void { $this->handle->setTitle($title); }
    public function shouldClose(): bool { return $this->closing; }
    public function swap(): void { $this->handle->swap(); }
    public function time(): float { return \PhpSdl\time(); }
    public function focus(): void { $this->handle->raise(); }
    public function focused(): bool { return $this->handle->focused(); }
    public function setCursor(string $cursor): void { $this->handle->setCursor($cursor); }
    public function clipboard(): string { return \PhpSdl\clipboard(); }
    public function setClipboard(string $text): void { \PhpSdl\setClipboard($text); }
    public function close(): void { $this->handle->close(); }

    public function textInput(?array $rect): void
    {
        if ($rect === $this->inputRect) return;
        $this->inputRect = $rect;
        $rect === null ? $this->handle->stopTextInput() : $this->handle->startTextInput(...array_map('intval', $rect));
    }

    public function waitEvents(float $timeout): void
    {
        $drops = [];
        foreach (\PhpSdl\events($timeout) as $e) {
            match ($e['type']) {
                'quit' => $this->closing = true,
                'pointer' => $this->emit('pointer', $e['x'], $e['y']),
                'button' => $this->emit('button', $e['button'], $e['pressed']),
                'scroll' => $this->emit('scroll', $e['dy']),
                'text' => $this->emit('text', $e['text']),
                'preedit' => $this->emit('preedit', $e['text'], $e['caret']),
                'key' => $this->emit('key', $e['key'], $e['mods']),
                'drop' => $drops[] = $e['path'],
                'refresh' => $this->emit('refresh'),
                default => null,
            };
        }
        // SDL sends one event per dropped file; the toolkit takes them together.
        if ($drops) $this->emit('drop', $drops);
    }
}
