// Phone number normalization & validation to mirror backend PhoneNumberService
// Default country: Cameroon (237)

export function normalize(phoneNumber: string, defaultCountryCode = '237'): string {
  if (!phoneNumber) return '';
  let cleaned = phoneNumber.replace(/[^\d+]/g, '');
  if (cleaned.startsWith('+')) cleaned = cleaned.slice(1);
  if (cleaned.startsWith('00')) cleaned = cleaned.slice(2);

  const len = cleaned.length;
  if (len === 12 && cleaned.startsWith('237')) return cleaned; // already intl
  if (len === 9 && cleaned.startsWith('6')) return defaultCountryCode + cleaned; // local cmr
  if (len === 8 && cleaned.startsWith('6')) return defaultCountryCode + '6' + cleaned; // missing leading 6
  if (len === 10) {
    if (cleaned.startsWith('6')) return defaultCountryCode + cleaned;
    return defaultCountryCode + cleaned;
  }
  if (len === 11) return cleaned;
  if (len === 13) {
    if (cleaned.startsWith('0')) return cleaned.slice(1);
    return cleaned;
  }
  if (len < 12) return defaultCountryCode + cleaned;
  return cleaned;
}

export function detectProvider(normalized: string): 'MTN_MOMO_CMR' | 'ORANGE_CMR' | 'UNKNOWN' {
  if (normalized.length !== 12 || !normalized.startsWith('237')) return 'UNKNOWN';
  const prefix3 = normalized.slice(3, 6);
  if (/^(65[0-9]|6[7-8][0-9])/.test(prefix3)) return 'MTN_MOMO_CMR';
  if (/^69[0-9]/.test(prefix3)) return 'ORANGE_CMR';
  return 'UNKNOWN';
}

export function validateCameroon(phoneNumber: string): { valid: boolean; error?: string; normalized: string; provider?: string } {
  const normalized = normalize(phoneNumber);
  if (normalized.length !== 12 || !normalized.startsWith('237')) {
    return { valid: false, error: 'Phone number must be a valid Cameroon number (e.g., 2376XXXXXXXX)', normalized };
  }
  const mobilePart = normalized.slice(3);
  if (!mobilePart.startsWith('6')) {
    return { valid: false, error: 'Phone number must be a valid Cameroon mobile number', normalized };
  }
  const provider = detectProvider(normalized);
  if (provider === 'UNKNOWN') {
    return { valid: false, error: 'Invalid mobile money number. Please enter a valid MTN or Orange mobile money number.', normalized };
  }
  return { valid: true, normalized, provider };
}

export function formatForDisplay(phoneNumber: string): string {
  const normalized = normalize(phoneNumber);
  if (normalized.length === 12 && normalized.startsWith('237')) {
    const mobile = normalized.slice(3);
    return `+237 ${mobile.slice(0,3)} ${mobile.slice(3,6)} ${mobile.slice(6)}`;
  }
  return normalized || '';
}

export function providerMeta(provider: string | undefined): { label: string; color: string } | null {
  switch (provider) {
    case 'MTN_MOMO_CMR':
      return { label: 'MTN MoMo', color: 'bg-yellow-400 text-black' };
    case 'ORANGE_CMR':
      return { label: 'Orange Money', color: 'bg-orange-500 text-white' };
    default:
      return null;
  }
}
