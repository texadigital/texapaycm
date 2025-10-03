import { AxiosError } from 'axios';

export function isUnauthorizedErr(e: any): boolean {
  const ax = e as AxiosError | undefined;
  const code = (ax?.response?.status as number | undefined) ?? undefined;
  return code === 401;
}
