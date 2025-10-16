// Lightweight in-memory store to mirror PWA's sessionStorage usage for transfer flow
// Note: This resets on app reload. Suitable for ephemeral flow steps.

export type Quote = {
  id: number;
  ref: string;
  amountXaf: number;
  feeTotalXaf: number;
  totalPayXaf: number;
  receiveNgnMinor: number;
  adjustedRate: number;
  expiresAt: string;
};

export type Recipient = { bankCode: string; bankName: string; account: string; accountName: string };

let selected: { quote?: Quote; recipient?: Recipient } | null = null;
let quoteState: { amount?: number; bankCode?: string; account?: string } | null = null;
let lastRatePreview: { rate?: number; at?: number } | null = null;
let lastRateImplied: { rate?: number; at?: number } | null = null;
let nameEnquiryRefs: Record<string, string> = {};

export function setSelectedQuote(payload: { quote: Quote; recipient: Recipient }) {
  selected = { quote: payload.quote, recipient: payload.recipient };
}
export function getSelectedQuote() { return selected; }
export function clearSelectedQuote() { selected = null; }

export function setQuoteState(state: { amount?: number; bankCode?: string; account?: string }) {
  quoteState = state;
}
export function getQuoteState() { return quoteState; }

export function setRatePreview(rate: number) { lastRatePreview = { rate, at: Date.now() }; }
export function getRatePreview() { return lastRatePreview; }

export function setRateImplied(rate: number) { lastRateImplied = { rate, at: Date.now() }; }
export function getRateImplied() { return lastRateImplied; }

export function setNameEnquiryRef(bankCode: string, account: string, ref: string) {
  nameEnquiryRefs[`${bankCode}:${account}`] = ref;
}
export function getNameEnquiryRef(bankCode: string, account: string) {
  return nameEnquiryRefs[`${bankCode}:${account}`] || null;
}
