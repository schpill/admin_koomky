"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { useProspectImportStore } from "@/lib/stores/prospect-import";
import { StepUpload } from "@/components/prospects/import-wizard/step-upload";
import { StepMapping } from "@/components/prospects/import-wizard/step-mapping";
import { StepOptions } from "@/components/prospects/import-wizard/step-options";
import { StepResults } from "@/components/prospects/import-wizard/step-results";

export default function ProspectImportPage() {
  const [step, setStep] = useState(1);
  const {
    session,
    columnList,
    previewRows,
    columnMapping,
    defaultTags,
    options,
    isUploading,
    isProcessing,
    progress,
    errors,
    uploadFile,
    updateMapping,
    updateOptions,
    processImport,
    exportErrors,
  } = useProspectImportStore();

  const onExportErrors = async () => {
    const blob = await exportErrors();
    if (!blob) return;

    const url = URL.createObjectURL(blob);
    const anchor = document.createElement("a");
    anchor.href = url;
    anchor.download = "import-errors.csv";
    anchor.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Import de prospects</h1>

      <div className="flex flex-wrap gap-2">
        {[1, 2, 3, 4].map((item) => (
          <Button
            key={item}
            variant={step === item ? "default" : "outline"}
            onClick={() => setStep(item)}
          >
            Etape {item}
          </Button>
        ))}
      </div>

      {step === 1 ? (
        <StepUpload isUploading={isUploading} onAnalyze={uploadFile} />
      ) : null}

      {step === 2 ? (
        <StepMapping
          columnList={columnList}
          previewRows={previewRows}
          mapping={columnMapping}
          onChange={updateMapping}
        />
      ) : null}

      {step === 3 ? (
        <StepOptions
          tags={defaultTags}
          defaultStatus={options.default_status}
          duplicateStrategy={options.duplicate_strategy}
          onChange={({ tags, defaultStatus, duplicateStrategy }) =>
            updateOptions({
              default_tags: tags,
              default_status: defaultStatus,
              duplicate_strategy: duplicateStrategy,
            })
          }
        />
      ) : null}

      {step === 4 ? (
        <StepResults
          session={session}
          progress={progress}
          errors={errors}
          isProcessing={isProcessing}
          onExportErrors={onExportErrors}
        />
      ) : null}

      <div className="flex justify-end">
        <Button onClick={processImport} disabled={!session || isProcessing}>
          Lancer l&apos;import
        </Button>
      </div>
    </div>
  );
}
