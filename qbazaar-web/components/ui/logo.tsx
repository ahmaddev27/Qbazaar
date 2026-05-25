import Image from 'next/image';
import { cn } from '@/lib/utils';

interface LogoProps {
  /** Render the wordmark next to the glyph (default: true). */
  withWordmark?: boolean;
  /** Extra classes applied to the wrapping span. */
  className?: string;
  /**
   * Pixel size of the glyph. The wordmark scales with the parent's
   * font-size so layouts that already set `text-xl` etc. just work.
   */
  size?: number;
}

/**
 * QBazaar brand mark. Pairs the existing coral PNG glyph (in /public/brand)
 * with the Instrument-Serif wordmark used throughout the Bazzar mockup.
 *
 * `priority` is on so the header logo doesn't flash on first paint.
 */
export function Logo({ withWordmark = true, className, size = 28 }: LogoProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-2 leading-none select-none',
        className,
      )}
    >
      <Image
        src="/brand/logo.png"
        alt="QBazaar"
        width={size}
        height={size}
        priority
        className="block"
      />
      {withWordmark ? (
        <span className="font-display text-coral text-xl tracking-tight">
          QBazaar
        </span>
      ) : null}
    </span>
  );
}
