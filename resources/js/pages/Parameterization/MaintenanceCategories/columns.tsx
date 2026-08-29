import { createColumnHelper } from "@tanstack/react-table"
 
import { type DataTableFeatures } from "./data-table-features"
import { MaintenanceCategory } from "@/types/maintenance/maintenance";

const columnHelper = createColumnHelper<DataTableFeatures, MaintenanceCategory>()


export const columns = columnHelper.columns([
    
        columnHelper.accessor("name", {
            header: "Nombre",
            cell: (info) => info.getValue(),
        }),
        columnHelper.accessor("is_active", {
            header: "Activo",
            cell: (info) => info.getValue(),
        }),
        columnHelper.accessor("description", {
            header: "Descripción",
            cell: (info) => info.getValue(),
        }),
        columnHelper.accessor("created_at", {
            header: "Creado en",
            cell: (info) => info.getValue(),
        }),
        columnHelper.accessor("updated_at", {
            header: "Actualizado en",
            cell: (info) => info.getValue(),
        }),
    ]
)