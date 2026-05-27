---
name: Livewire 4 single root element
description: A <style> or any tag placed BEFORE the root <div> in a Livewire component view silently kills ALL wire:click events.
---

## Rule
A Livewire 4 component view must have exactly **one** root element.

## Why
Placing a `<style>` block (or any tag) *before* the root `<div>` creates a second root element. Livewire silently fails to attach its Alpine.js event listeners — the page renders fine but every `wire:click`, `wire:submit`, etc. is dead. No error is thrown.

## How to apply
- Always place `<style>` blocks **inside** the root element as the first child.
- Never put `<style>` or `<script>` as siblings of the root element.
- Correct pattern:

```html
<div wire:poll.4000ms ...>    ← single root
  <style>...</style>           ← first child, inside root
  <!-- rest of component -->
</div>
```

- Wrong pattern:

```html
<style>...</style>             ← second root — breaks Livewire
<div wire:poll.4000ms ...>
  <!-- rest of component -->
</div>
```
