<?php
/**
 * SkinExpert SVG Icon Helper
 * Menyediakan ikon vektor SVG modern, tajam, dan responsif untuk menggantikan emoji.
 */

function icon(string $name, string $extraClass = '', int $size = 20): string {
    $classAttr = 'svg-icon' . ($extraClass !== '' ? ' ' . htmlspecialchars($extraClass) : '');
    $s = $size;

    $icons = [
        // Brand / Daun / Skin Care
        'leaf' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/>
  <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
</svg>
SVG,

        // Home
        'home' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
  <polyline points="9 22 9 12 15 12 15 22"/>
</svg>
SVG,

        // Konsultasi / Chat / Message
        'chat' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
</svg>
SVG,

        // Latihan / Lab / Flask
        'flask' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M10 2v7.31L4.1 19.34A2 2 0 0 0 5.8 22h12.4a2 2 0 0 0 1.7-2.66L14 9.31V2"/>
  <path d="M8.5 2h7"/>
  <path d="M7 16h10"/>
</svg>
SVG,

        // Search / Gejala / Fakta
        'search' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="11" cy="11" r="8"/>
  <path d="m21 21-4.3-4.3"/>
</svg>
SVG,

        // Target / Hipotesis / Masalah (Goal)
        'target' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="12" cy="12" r="10"/>
  <circle cx="12" cy="12" r="6"/>
  <circle cx="12" cy="12" r="2"/>
</svg>
SVG,

        // Aturan / Rule / Branch / Nodes
        'rule' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="18" cy="18" r="3"/>
  <circle cx="6" cy="6" r="3"/>
  <path d="M13 6h3a2 2 0 0 1 2 2v7"/>
  <line x1="6" y1="9" x2="6" y2="21"/>
</svg>
SVG,

        // Riwayat / Clipboard
        'clipboard' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
  <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
  <path d="M12 11h4"/>
  <path d="M12 16h4"/>
  <path d="M8 11h.01"/>
  <path d="M8 16h.01"/>
</svg>
SVG,

        // Settings / Menu Gear
        'settings' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <line x1="4" x2="20" y1="12" y2="12"/>
  <line x1="4" x2="20" y1="6" y2="6"/>
  <line x1="4" x2="20" y1="18" y2="18"/>
</svg>
SVG,

        // Checkmark / Sukses / Terbukti
        'check' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
  <polyline points="20 6 9 17 4 12"/>
</svg>
SVG,

        // Check Circle
        'check-circle' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
  <polyline points="22 4 12 14.01 9 11.01"/>
</svg>
SVG,

        // Alert / Warning
        'alert' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="12" cy="12" r="10"/>
  <line x1="12" y1="8" x2="12" y2="12"/>
  <line x1="12" y1="16" x2="12.01" y2="16"/>
</svg>
SVG,

        // Lightbulb / Verifikasi / Ide
        'lightbulb' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/>
  <path d="M9 18h6"/>
  <path d="M10 22h4"/>
</svg>
SVG,

        // Stethoscope / Solusi Klinis
        'stethoscope' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/>
  <path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/>
  <circle cx="20" cy="10" r="2"/>
</svg>
SVG,

        // Rocket / Launch / Action
        'rocket' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
  <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>
  <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/>
  <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>
</svg>
SVG,

        // Refresh / Ulangi
        'refresh' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
  <path d="M3 3v5h5"/>
  <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
  <path d="M16 21h5v-5"/>
</svg>
SVG,

        // Trash / Hapus
        'trash' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M3 6h18"/>
  <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
  <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
</svg>
SVG,

        // Edit / Ubah
        'edit' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
  <path d="m15 5 4 4"/>
</svg>
SVG,

        // Shield Check (Aman / Tervalidasi)
        'shield' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
</svg>
SVG,

        // Sparkles / Cantik / Clean
        'sparkles' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/>
</svg>
SVG,

        // Pin
        'pin' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <line x1="12" y1="17" x2="12" y2="22"/>
  <path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"/>
</svg>
SVG,

        // Close / X
        'close' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M18 6 6 18"/>
  <path d="m6 6 12 12"/>
</svg>
SVG,

        // Arrow Left
        'arrow-left' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <line x1="19" y1="12" x2="5" y2="12"/>
  <polyline points="12 19 5 12 12 5"/>
</svg>
SVG,

        // Academic / Grad Cap
        'academic' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
  <path d="M6 12v5c3 3 9 3 12 0v-5"/>
</svg>
SVG,

        // Database
        'database' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <ellipse cx="12" cy="5" rx="9" ry="3"/>
  <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
  <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
</svg>
SVG,

        // Plus
        'plus' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <line x1="12" y1="5" x2="12" y2="19"/>
  <line x1="5" y1="12" x2="19" y2="12"/>
</svg>
SVG,

        // Zap / Listrik / Fast
        'zap' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
</svg>
SVG,

        // Monitor / Screen
        'monitor' => <<<SVG
<svg class="{$classAttr}" width="{$s}" height="{$s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <rect width="20" height="14" x="2" y="3" rx="2"/>
  <line x1="8" y1="21" x2="16" y2="21"/>
  <line x1="12" y1="17" x2="12" y2="21"/>
</svg>
SVG
    ];

    return $icons[$name] ?? '';
}
