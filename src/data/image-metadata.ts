export const imageMetadata: Record<string, { width: number; height: number }> = {
  "/images/khonggian.jpg": { width: 2560, height: 1438 },
  "/images/hero-image.jpg": { width: 2568, height: 1926 },
  "/images/hero/727457690_1059334386607528_499987599721631516_n.jpg": { width: 2048, height: 1151 },
};

export function getImageMetadata(src: string) {
  return imageMetadata[src] ?? { width: 1200, height: 675 };
}
