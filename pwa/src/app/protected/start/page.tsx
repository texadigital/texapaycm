"use client";
import { useEffect } from "react";
import { useRouter } from "next/navigation";

export default function StartProtectedPage() {
  const router = useRouter();
  useEffect(() => { router.replace("/protected/verify"); }, [router]);
  return null;
}
