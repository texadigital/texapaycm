"use client";
import React from "react";
import { useParams } from "next/navigation";
import { useMutation, useQuery } from "@tanstack/react-query";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

type TransferDetails = {
  id: number;
  status: string;
  createdAt?: string;
  recipientGetsMinor?: number;
  recipientGetsCurrency?: string;
  accountName?: string;
  bankName?: string;
  accountNumber?: string;
  payerMsisdn?: string;
  payerName?: string;
  transactionNo?: string;
  sessionId?: string | null;
  receiptFooterText?: string | null;
  receiptWatermarkUrl?: string | null;
};

export default function ReceiptPage() {
  const params = useParams<{ id: string }>();
  const id = params?.id;

  const details = useQuery<TransferDetails>({
    queryKey: ["transfer-details", id, "receipt"],
    queryFn: async () => {
      const res = await http.get(`/api/mobile/transfers/${id}`);
      return res.data as any;
    },
    enabled: !!id,
  });

  const pdf = useMutation({
    mutationFn: async () => {
      const node = refCard.current;
      if (!node) throw new Error('No receipt');
      const html2canvas = await ensureHtml2Canvas();
      const jsPDF = await ensureJsPDF();
      const canvas = await html2canvas(node, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
      const imgData = canvas.toDataURL('image/png');
      // Create a PDF sized to the image (A4 portrait fallback)
      const pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
      const pageWidth = pdf.internal.pageSize.getWidth();
      const pageHeight = pdf.internal.pageSize.getHeight();
      const imgWidth = pageWidth - 48; // margins
      const ratio = canvas.height / canvas.width;
      const imgHeight = imgWidth * ratio;
      let y = 24;
      if (imgHeight > pageHeight - 48) {
        // scale down to fit height too
        const imgHeightFit = pageHeight - 48;
        const imgWidthFit = imgHeightFit / ratio;
        pdf.addImage(imgData, 'PNG', (pageWidth - imgWidthFit) / 2, y, imgWidthFit, imgHeightFit, undefined, 'FAST');
      } else {
        pdf.addImage(imgData, 'PNG', (pageWidth - imgWidth) / 2, y, imgWidth, imgHeight, undefined, 'FAST');
      }
      const blob = pdf.output('blob');
      const file = new File([blob], `receipt-${id}.pdf`, { type: 'application/pdf' });
      if ((navigator as any).share && (navigator as any).canShare?.({ files: [file] })) {
        try { await (navigator as any).share({ files: [file], title: 'Transaction Receipt' }); return true; } catch {}
      }
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = `receipt-${id}.pdf`;
      document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
      return true;
    },
  });

  const refCard = React.useRef<HTMLDivElement>(null);

  async function ensureHtml2Canvas(): Promise<any> {
    const w = window as any;
    if (w.html2canvas) return w.html2canvas;
    await new Promise<void>((resolve, reject) => {
      const s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
      s.onload = () => resolve();
      s.onerror = () => reject(new Error('Failed to load html2canvas'));
      document.head.appendChild(s);
    });
    return (window as any).html2canvas;
  }

  async function ensureJsPDF(): Promise<any> {
    const w = window as any;
    if (w.jspdf) return w.jspdf.jsPDF || w.jsPDF;
    await new Promise<void>((resolve, reject) => {
      const s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js';
      s.onload = () => resolve();
      s.onerror = () => reject(new Error('Failed to load jsPDF'));
      document.head.appendChild(s);
    });
    const w2 = window as any;
    return w2.jspdf?.jsPDF || w2.jsPDF;
  }

  const shareImage = useMutation({
    mutationFn: async () => {
      const node = refCard.current;
      if (!node) throw new Error('No receipt');
      const html2canvas = await ensureHtml2Canvas();
      const canvas = await html2canvas(node, { scale: 2, useCORS: true, backgroundColor: null });
      return new Promise<void>((resolve) => {
        canvas.toBlob(async (blob: Blob | null) => {
          if (!blob) return resolve();
          const file = new File([blob], `receipt-${id}.png`, { type: 'image/png' });
          if ((navigator as any).share && (navigator as any).canShare?.({ files: [file] })) {
            try { await (navigator as any).share({ files: [file], title: 'Transaction Receipt' }); } catch {}
          } else {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = `receipt-${id}.png`;
            document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
          }
          resolve();
        }, 'image/png');
      });
    },
  });

  function maskMsisdn(msisdn?: string) {
    if (!msisdn) return '—';
    const d = msisdn.replace(/\D/g, '');
    if (d.length <= 6) return d;
    const first = d.slice(0,3);
    const last = d.slice(-3);
    return `${first}****${last}`;
  }

  function fmtMoney(minor: number, currency: string) {
    const divisor = 100;
    const symbol = currency === 'NGN' ? '₦' : currency === 'XAF' ? 'XAF ' : `${currency} `;
    return `${symbol}${(minor / divisor).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
  }

  const statusLabel = React.useMemo(() => {
    const s = details.data?.status || '';
    if (s.includes('failed')) return 'Failed';
    if (s.includes('success')) return 'Successful';
    return 'Pending';
  }, [details.data?.status]);

  return (
    <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-4">
      <PageHeader title="Share Receipt">
        <button className="border rounded px-3 py-1" onClick={() => details.refetch()} disabled={details.isFetching}>
          {details.isFetching ? 'Refreshing...' : 'Refresh'}
        </button>
      </PageHeader>

      {details.isLoading && (
        <div className="space-y-3"><CardSkeleton lines={3} /></div>
      )}
      {details.error && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">
          {(details.error as any)?.response?.data?.message || (details.error as Error).message}
        </div>
      )}

      {details.data && (
        <div className="space-y-4">
          {/* Receipt card to be exported */}
          <div ref={refCard} className="rounded-xl border p-5 bg-white relative overflow-hidden" style={{
            backgroundImage: details.data.receiptWatermarkUrl ? `url(${details.data.receiptWatermarkUrl})` : undefined,
            backgroundRepeat: 'repeat', backgroundSize: '200px', backgroundPosition: 'center', opacity: 1
          }}>
            {/* subtle overlay to tone down watermark */}
            <div className="absolute inset-0 opacity-5 bg-white pointer-events-none" />
            <div className="relative space-y-4">
              {/* Header */}
              <div className="flex items-start justify-between">
                <div className="text-emerald-700 font-semibold">TexaPay</div>
                <div className="text-right text-gray-600">Transaction Receipt</div>
              </div>
              <div className="text-center space-y-1">
                <div className="text-3xl font-extrabold">
                  {fmtMoney(details.data.recipientGetsMinor || 0, details.data.recipientGetsCurrency || 'NGN')}
                </div>
                <div className={`inline-block text-xs px-2 py-0.5 rounded ${statusLabel === 'Successful' ? 'bg-emerald-50 text-emerald-700' : statusLabel === 'Failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700'}`}>{statusLabel}</div>
                <div className="text-xs text-gray-600" suppressHydrationWarning>{details.data.createdAt ? new Date(details.data.createdAt).toLocaleString() : '—'}</div>
              </div>
              <hr className="border-dashed" />
              {/* Recipient + Sender */}
              <div className="grid grid-cols-2 gap-6 text-sm">
                <div>
                  <div className="text-xs text-gray-500 mb-1">Recipient Details</div>
                  <div className="font-medium">{details.data.accountName || '—'}</div>
                  <div className="text-gray-700">{details.data.bankName || '—'} | {details.data.accountNumber || '—'}</div>
                </div>
                <div>
                  <div className="text-xs text-gray-500 mb-1">Sender Details</div>
                  <div className="font-medium">{details.data.payerName || '—'}</div>
                  <div className="text-gray-700">MoMo | {maskMsisdn(details.data.payerMsisdn)}</div>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-6 text-sm">
                <div>
                  <div className="text-xs text-gray-500 mb-1">Transaction No.</div>
                  <div className="flex items-center gap-2 min-w-0"><span className="font-mono text-xs truncate" title={String(details.data.transactionNo || details.data.id)}>{details.data.transactionNo || details.data.id}</span><span className="text-xs underline cursor-pointer whitespace-nowrap" onClick={() => { try { navigator.clipboard.writeText(String(details.data.transactionNo || details.data.id)); } catch {} }}>Copy</span></div>
                </div>
                <div>
                  <div className="text-xs text-gray-500 mb-1">Session ID</div>
                  <div className="flex items-center gap-2 min-w-0"><span className="font-mono text-xs truncate" title={details.data.sessionId || '—'}>{details.data.sessionId || '—'}</span><span className="text-xs underline cursor-pointer whitespace-nowrap" onClick={() => { if (details.data?.sessionId) { try { navigator.clipboard.writeText(details.data.sessionId); } catch {} } }}>Copy</span></div>
                </div>
              </div>
              <hr className="border-dashed" />
              {details.data.receiptFooterText ? (
                <div className="text-xs text-gray-700 whitespace-pre-wrap">{details.data.receiptFooterText}</div>
              ) : null}
            </div>
          </div>

          {/* Actions */}
          <div className="flex items-center justify-between gap-3">
            <button className="flex-1 border rounded px-3 py-2" onClick={() => shareImage.mutate()} disabled={shareImage.isPending}>
              {shareImage.isPending ? 'Preparing…' : 'Share as image'}
            </button>
            <button className="flex-1 bg-black text-white rounded px-3 py-2" onClick={() => pdf.mutate()} disabled={pdf.isPending}>
              {pdf.isPending ? 'Preparing…' : 'Share as PDF'}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
