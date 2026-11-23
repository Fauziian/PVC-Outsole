import React from "react";
import { router } from "@inertiajs/react";
import { ChevronLeft, ChevronRight } from "lucide-react";

interface PaginationProps {
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
  preserveState?: boolean;
  searchParams?: Record<string, any>;
}

export default function Pagination({
  links,
  preserveState = true,
  searchParams = {},
}: PaginationProps) {
  if (!links || links.length <= 3) return null;

  return (
    <div className="flex items-center gap-1">
      {links.map((link, idx) => {
        const isPrev = idx === 0;
        const isNext = idx === links.length - 1;

        const handleClick = () => {
          if (!link.url || link.active) return;
          router.get(link.url, searchParams, { preserveState });
        };

        let content: React.ReactNode = (
          <span dangerouslySetInnerHTML={{ __html: link.label }} />
        );

        if (isPrev) {
          content = <ChevronLeft size={15} />;
        } else if (isNext) {
          content = <ChevronRight size={15} />;
        }

        return (
          <button
            key={idx}
            disabled={!link.url || link.active}
            onClick={handleClick}
            className={`flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-xs font-semibold transition-all ${
              link.active
                ? "bg-blue-600 text-white shadow-sm ring-1 ring-blue-600 font-bold"
                : "bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 hover:text-slate-900 disabled:opacity-40 disabled:hover:bg-white"
            } ${isPrev || isNext ? "px-2" : ""}`}
            title={isPrev ? "Sebelumnya" : isNext ? "Berikutnya" : `Halaman ${link.label}`}
          >
            {content}
          </button>
        );
      })}
    </div>
  );
}
