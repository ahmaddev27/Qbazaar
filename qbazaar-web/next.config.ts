import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    // Disabled until the production storage host (S3/CDN/Laravel /storage) is
    // finalised. Once known, switch to `remotePatterns` and remove this flag
    // to re-enable Vercel's image optimisation pipeline.
    unoptimized: true,
  },
};

export default nextConfig;
