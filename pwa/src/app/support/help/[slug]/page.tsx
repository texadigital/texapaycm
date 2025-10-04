import RequireAuth from "@/components/guards/require-auth";
import PageHeader from "@/components/ui/page-header";

export const dynamicParams = false; // ensure purely static paths

export function generateStaticParams() {
  // Statically generate a small set of help slugs. Others can be accessed via /support/help?slug=
  return [
    { slug: "getting-started" },
    { slug: "faq" },
    { slug: "fees" },
    { slug: "limits" },
    { slug: "security" },
  ];
}

export default function HelpArticlePage({ params }: { params: { slug: string } }) {
  const slug = params?.slug || "getting-started";
  const base = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") || "";
  const url = `${base}/support/help/${slug}`;

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-4xl mx-auto space-y-4">
        <PageHeader title="Help">
          <a className="border rounded px-3 py-1" href={url}>Open in tab</a>
        </PageHeader>
        <div className="border rounded overflow-hidden" style={{ height: 700 }}>
          <iframe src={url} title={slug} className="w-full h-full" />
        </div>
      </div>
    </RequireAuth>
  );
}
