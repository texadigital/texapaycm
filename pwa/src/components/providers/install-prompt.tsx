"use client";
import React from "react";

export default function InstallPromptProvider() {
  const [deferred, setDeferred] = React.useState<any>(null);
  const [visible, setVisible] = React.useState(false);

  React.useEffect(() => {
    const onBeforeInstall = (e: any) => {
      // Prevent Chrome 67 and earlier from automatically showing the prompt
      e.preventDefault();
      setDeferred(e);
      setVisible(true);
    };
    window.addEventListener("beforeinstallprompt", onBeforeInstall as any);
    return () => window.removeEventListener("beforeinstallprompt", onBeforeInstall as any);
  }, []);

  if (!visible) return null;

  return (
    <div className="fixed bottom-20 left-1/2 -translate-x-1/2 z-50 bg-black text-white text-sm px-3 py-2 rounded shadow">
      <span>Install TexaPay for quicker access</span>
      <button
        className="ml-3 underline"
        onClick={async () => {
          try {
            if (!deferred) return;
            deferred.prompt();
            const { outcome } = await deferred.userChoice;
            // Hide after choice
            setVisible(false);
            setDeferred(null);
          } catch {
            setVisible(false);
          }
        }}
      >
        Install
      </button>
      <button className="ml-2 opacity-80" onClick={() => setVisible(false)}>Dismiss</button>
    </div>
  );
}
