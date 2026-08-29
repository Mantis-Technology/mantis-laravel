import { Link, usePage } from "@inertiajs/react";
import { Plus } from "lucide-react";

import { columns } from "./columns";
import { DataTable } from "./data-table";

export default function MaintenanceCategoriesIndex() {
  const { maintenance_categories } = usePage().props;

  return (
    <div className="container mx-auto py-10">
      <div className="mb-8 flex items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">
            Categorías de mantenimiento
          </h1>

          <p className="mt-2 text-sm text-muted-foreground">
            Administra las categorías utilizadas para clasificar y organizar
            las actividades de mantenimiento.
          </p>
        </div>

        <Link
          href={"/create"}
          className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
        >
          <Plus className="h-4 w-4" />
          Nueva categoría
        </Link>
      </div>

      <DataTable
        columns={columns}
        data={maintenance_categories}
      />
    </div>
  );
}