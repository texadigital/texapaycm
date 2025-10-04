import http from "@/lib/api";

export type BankMeta = {
  code: string;
  name: string;
  shortName?: string;
  logoUrl?: string | null;
  alphaKey: string;
};

let directory: Record<string, BankMeta> | null = null;
let directoryList: BankMeta[] = [];
let loadingPromise: Promise<BankMeta[]> | null = null;

// Small local logo hints; safe fallback to initials when missing
const LOGO_HINTS: Record<string, string> = {
  "000014": "/banks/access.png",
  "000004": "/banks/gtb.png",
  "000016": "/banks/firstbank.png",
  "000013": "/banks/fidelity.png",
  "000001": "/banks/sterling.png",
  "000023": "/banks/zenith.png",
  "999240": "/banks/safehaven.png",
};

export async function loadBankDirectory(force = false): Promise<BankMeta[]> {
  if (directory && !force) return directoryList;
  if (loadingPromise && !force) return loadingPromise;
  loadingPromise = (async () => {
    try {
      const res = await http.get("/api/mobile/banks");
      const banks = (res.data?.banks || []) as Array<{ bankCode: string; name: string; aliases?: string[] }>;
      const list: BankMeta[] = banks.map((b) => {
        const name = String(b.name || "").trim();
        const code = String(b.bankCode || "").trim();
        const alphaKey = (name[0] || "#").toUpperCase();
        const logoUrl = LOGO_HINTS[code] || null;
        return { code, name, logoUrl, alphaKey };
      }).filter(b => b.code && b.name);
      directory = Object.fromEntries(list.map((b) => [b.code, b]));
      directoryList = list;
      return list;
    } catch {
      directory = directory || {};
      directoryList = directoryList || [];
      return directoryList;
    } finally {
      loadingPromise = null;
    }
  })();
  return loadingPromise;
}

export function bankMetaByCode(code?: string | null): BankMeta | null {
  if (!code) return null;
  const c = String(code);
  return (directory && directory[c]) || null;
}

export function resolveBankName(code?: string | null, fallback?: string | null): string {
  const meta = bankMetaByCode(code);
  return meta?.name || fallback || (code ? String(code) : "");
}

export function allBanks(): BankMeta[] {
  return directoryList;
}
