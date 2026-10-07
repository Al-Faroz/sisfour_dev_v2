# SisFour Android Branding Source

## Final G4.1 source of truth

User-provided branding masters received 6 October 2026:

- `LogoFlat.svg` — color vector master.
- `LogoFlat_White.svg` — white vector master.
- matching PNG uploads are visual/raster references.

Android launcher/splash raster master:

- `launcher-master-1024.png` — exact 1024 × 1024 raster derived deterministically from the user-provided square color SVG; artwork is not redrawn.
- transparent square canvas.
- used as `drawable-nodpi/sisfour_brand_logo.png` for adaptive foreground and native splash.

Legacy launcher density assets:

```text
icon-ldpi.png       36 × 36 px
icon-mdpi.png       48 × 48 px
icon-hdpi.png       72 × 72 px
icon-xhdpi.png      96 × 96 px
icon-xxhdpi.png    144 × 144 px
icon-xxxhdpi.png   192 × 192 px
```

Launcher icon:

- launcher uses the user-provided density PNGs directly:
  - 36 × 36 px ldpi
  - 48 × 48 px mdpi
  - 72 × 72 px hdpi
  - 96 × 96 px xhdpi
  - 144 × 144 px xxhdpi
  - 192 × 192 px xxxhdpi
- no adaptive foreground/background XML is used by the launcher pipeline.
- this avoids routing Android modern launchers through a separate generated adaptive foreground path.

Splash/opening handoff:

```text
native Android splash
→ user-provided splash-color-1024.png scaled into the splash item on white
→ local Cordova shell
→ user-provided splash-white-1024.png on #119450
→ controlled SisFour InAppBrowser
```

The shell transition is cosmetic only (320 ms opacity/scale) and adds no artificial startup delay. `prefers-reduced-motion` disables it.

## Retired source

The earlier non-square mobile master (`mtsn4jombang-logo-master.png`, approximately 2319 × 2299) is retired and removed from the mobile branding directory. It must not be referenced by `config.xml`, launcher resources, adaptive icon, or splash.

The original Website branding upload remains untouched because it belongs to the Web application, not the Android launcher asset pipeline.

## Rules

- no Apache Cordova placeholder/default branding;
- do not edit user-provided SVG masters in place;
- do not redraw the official logo;
- icon/splash resource changes require clean-build + clean-install device UAT;
- release keystore/signing material never belongs in this folder or ordinary repository source.
