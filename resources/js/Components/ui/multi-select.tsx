import { Fragment } from "react"
import { ChevronDownIcon } from "lucide-react"

import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

interface MultiSelectOption {
  value: string
  label: string
}

interface MultiSelectGroup {
  label?: string
  options: MultiSelectOption[]
}

interface MultiSelectProps {
  groups: MultiSelectGroup[]
  value: string[]
  onChange: (value: string[]) => void
  placeholder?: string
  disabled?: boolean
  className?: string
}

function MultiSelect({
  groups,
  value,
  onChange,
  placeholder = "Seleccionar...",
  disabled = false,
  className,
}: MultiSelectProps) {
  const options = groups.flatMap((group) => group.options)
  const selected = new Set(value)
  const selectedLabels = options
    .filter((option) => selected.has(option.value))
    .map((option) => option.label)

  const label =
    selectedLabels.length === 0
      ? placeholder
      : selectedLabels.length <= 2
        ? selectedLabels.join(", ")
        : `${selectedLabels.length} seleccionados`

  function toggle(optionValue: string, checked: boolean) {
    if (checked) {
      onChange([...value, optionValue])
    } else {
      onChange(value.filter((item) => item !== optionValue))
    }
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        disabled={disabled}
        render={
          <Button
            type="button"
            variant="outline"
            className={cn("w-full justify-between font-normal", className)}
          />
        }
      >
        <span
          className={cn(
            "min-w-0 flex-1 truncate text-left",
            selectedLabels.length === 0 && "text-muted-foreground"
          )}
        >
          {label}
        </span>
        <ChevronDownIcon className="size-4 shrink-0 opacity-50" />
      </DropdownMenuTrigger>

      <DropdownMenuContent
        align="start"
        className="max-h-72 w-(--anchor-width) min-w-56 overflow-y-auto"
      >
        {groups.map((group, index) => (
          <Fragment key={group.label ?? index}>
            {index > 0 && <DropdownMenuSeparator />}

            <DropdownMenuGroup>
              {group.label && (
                <DropdownMenuLabel>{group.label}</DropdownMenuLabel>
              )}

              {group.options.map((option) => (
                <DropdownMenuCheckboxItem
                  key={option.value}
                  checked={selected.has(option.value)}
                  closeOnClick={false}
                  onCheckedChange={(checked) =>
                    toggle(option.value, checked)
                  }
                >
                  {option.label}
                </DropdownMenuCheckboxItem>
              ))}
            </DropdownMenuGroup>
          </Fragment>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}

export { MultiSelect }
export type { MultiSelectGroup, MultiSelectOption }
