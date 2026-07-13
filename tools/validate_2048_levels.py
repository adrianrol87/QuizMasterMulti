from __future__ import annotations

import csv
import math
import sys
from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class Goal:
    value: int
    count: int

    @property
    def depth(self) -> int:
        if self.value < 4 or self.count <= 0:
            return 0
        return int(math.log2(self.value)) - 1

    @property
    def merge_steps(self) -> int:
        return self.depth * self.count


def parse_goals(row: dict[str, str]) -> list[Goal]:
    goals: list[Goal] = []
    for index in range(1, 4):
        value = int(row.get(f"objetivo_{index}_valor", "0") or "0")
        count = int(row.get(f"objetivo_{index}_cantidad", "0") or "0")
        if value > 0 and count > 0:
            goals.append(Goal(value=value, count=count))
    return goals


def theoretical_floor(goals: list[Goal]) -> int:
    if not goals:
        return 0
    max_depth = max(goal.depth for goal in goals)
    total_merge_steps = sum(goal.merge_steps for goal in goals)
    # Lower bound heuristic:
    # - reaching the deepest tile chain takes at least its own build depth
    # - several merge chains can happen in the same swipe, so divide by 3 as a soft floor
    # - multiple simultaneous goals still need some setup separation
    return max(max_depth + len(goals) - 1, math.ceil(total_merge_steps / 3))


def recommended_moves(goals: list[Goal]) -> int:
    if not goals:
        return 0
    total_merge_steps = sum(goal.merge_steps for goal in goals)
    total_tiles = sum(goal.count for goal in goals)
    max_depth = max(goal.depth for goal in goals)
    high_tile_pressure = sum(max(0, goal.depth - 4) * goal.count for goal in goals)
    duplicate_pressure = sum(max(0, goal.count - 1) * max(1, goal.depth - 1) for goal in goals)
    diversity_penalty = max(0, len(goals) - 1) * 2
    density_penalty = max(0, total_tiles - 2)

    estimate = (
        total_merge_steps * 1.65
        + max_depth * 0.9
        + high_tile_pressure * 1.4
        + duplicate_pressure * 0.9
        + diversity_penalty
        + density_penalty
    )

    return math.ceil(estimate)


def classify(moves: int, floor: int, recommended: int) -> str:
    if moves < floor:
        return "probablemente imposible"
    if moves < recommended:
        return "muy justo"
    if moves <= recommended + 3:
        return "justo"
    if moves <= recommended + 8:
        return "alcanzable"
    return "holgado"


def explain(moves: int, floor: int, recommended: int) -> str:
    if moves < floor:
        return "por debajo del piso teorico"
    if moves < recommended:
        return "por debajo del rango recomendado"
    if moves <= recommended + 3:
        return "en el limite recomendado"
    if moves <= recommended + 8:
        return "margen sano para un jugador bueno"
    return "margen amplio para jugador promedio"


def build_report(input_path: Path) -> Path:
    output_path = input_path.with_name(f"{input_path.stem}_reporte.csv")
    with input_path.open("r", encoding="utf-8-sig", newline="") as source:
        reader = csv.DictReader(source)
        rows = list(reader)

    report_rows: list[dict[str, str | int]] = []
    for row in rows:
        goals = parse_goals(row)
        moves = int(row["movimientos"])
        floor = theoretical_floor(goals)
        recommended = recommended_moves(goals)
        report_rows.append(
            {
                **row,
                "piso_teorico": floor,
                "movimientos_recomendados": recommended,
                "delta_vs_recomendado": moves - recommended,
                "estado_validacion": classify(moves, floor, recommended),
                "comentario_validacion": explain(moves, floor, recommended),
            }
        )

    fieldnames = list(report_rows[0].keys()) if report_rows else []
    with output_path.open("w", encoding="utf-8", newline="") as target:
        writer = csv.DictWriter(target, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(report_rows)

    return output_path


def main() -> int:
    if len(sys.argv) < 2:
        print("Uso: python tools/validate_2048_levels.py <archivo.csv>")
        return 1

    input_path = Path(sys.argv[1]).resolve()
    if not input_path.exists():
        print(f"No existe el archivo: {input_path}")
        return 1

    output_path = build_report(input_path)
    print(f"Reporte generado en: {output_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
