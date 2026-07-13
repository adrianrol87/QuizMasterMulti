from __future__ import annotations

import csv
import math
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


def recommended_moves(goals: list[Goal]) -> int:
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


SPECS = [
    ((4, 2), (), (), "muy facil", "aprender a fusionar pares"),
    ((8, 1), (), (), "muy facil", "primer ocho con espacio"),
    ((8, 2), (), (), "muy facil", "doble ocho sencillo"),
    ((16, 1), (), (), "muy facil", "primer dieciseis"),
    ((8, 2), (16, 1), (), "muy facil", "mezcla corta de metas"),
    ((16, 2), (), (), "muy facil", "dos dieciseis controlados"),
    ((32, 1), (), (), "muy facil", "primer treinta y dos"),
    ((16, 2), (32, 1), (), "muy facil", "subida natural"),
    ((32, 2), (), (), "muy facil", "dos treinta y dos posibles"),
    ((64, 1), (), (), "muy facil", "primer sesenta y cuatro"),
    ((8, 3), (), (), "facil", "volumen bajo y estable"),
    ((16, 2), (), (), "facil", "repetir fusion media"),
    ((32, 1), (8, 2), (), "facil", "objetivo alto con soporte chico"),
    ((32, 2), (), (), "facil", "doble treinta y dos"),
    ((16, 3), (32, 1), (), "facil", "controlar tres rutas"),
    ((64, 1), (16, 2), (), "facil", "subida a sesenta y cuatro"),
    ((32, 2), (64, 1), (), "facil", "mantener tablero ordenado"),
    ((64, 2), (), (), "facil", "doble sesenta y cuatro"),
    ((32, 3), (), (), "facil", "ritmo constante"),
    ((128, 1), (), (), "facil", "primer ciento veintiocho"),
    ((16, 3), (32, 1), (), "medio", "transicion estable"),
    ((64, 1), (32, 2), (), "medio", "apilar sin romper la esquina"),
    ((32, 2), (64, 1), (), "medio", "retos cruzados ligeros"),
    ((64, 2), (), (), "medio", "doble sesenta y cuatro consistente"),
    ((128, 1), (32, 1), (), "medio", "objetivo alto con apoyo"),
    ((32, 3), (64, 1), (), "medio", "densidad moderada"),
    ((64, 2), (16, 2), (), "medio", "sostener dos cadenas"),
    ((128, 1), (64, 1), (), "medio", "escalera corta"),
    ((32, 4), (), (), "medio", "limpieza y control"),
    ((128, 1), (64, 2), (), "medio", "primer cierre serio"),
    ((64, 3), (), (), "medio", "volumen de sesenta y cuatro"),
    ((128, 1), (32, 2), (), "medio", "pico con soporte"),
    ((64, 2), (128, 1), (), "medio", "rotacion de objetivos"),
    ((128, 2), (), (), "medio", "doble ciento veintiocho"),
    ((32, 2), (64, 2), (), "medio", "dos capas sin excesos"),
    ((128, 1), (64, 2), (), "intermedio", "planeacion gradual"),
    ((256, 1), (), (), "intermedio", "primer doscientos cincuenta y seis"),
    ((64, 3), (128, 1), (), "intermedio", "apilar y rematar"),
    ((256, 1), (32, 2), (), "intermedio", "objetivo alto con base simple"),
    ((128, 2), (64, 1), (), "intermedio", "doble capa media"),
    ((256, 1), (64, 1), (), "intermedio", "escalar sin perder orden"),
    ((64, 4), (), (), "intermedio", "volumen horizontal"),
    ((128, 2), (), (), "intermedio", "repeticion alta controlada"),
    ((256, 1), (64, 2), (), "intermedio", "dificultad sostenida"),
    ((128, 3), (), (), "intermedio", "tres ciento veintiocho"),
    ((256, 2), (), (), "avanzado", "doble doscientos cincuenta y seis"),
    ((64, 2), (128, 2), (), "avanzado", "densidad sin pico extremo"),
    ((256, 1), (128, 1), (), "avanzado", "escalon dual"),
    ((512, 1), (), (), "avanzado", "primer quinientos doce"),
    ((128, 2), (256, 1), (), "avanzado", "antesala de quinientos doce"),
    ((256, 2), (64, 1), (), "avanzado", "control con soporte bajo"),
    ((128, 3), (64, 1), (), "avanzado", "tablero poblado pero posible"),
    ((512, 1), (64, 1), (), "avanzado", "pico alto con ayuda simple"),
    ((256, 1), (128, 2), (), "avanzado", "cadencia doble"),
    ((64, 4), (128, 1), (), "avanzado", "ancho con cierre medio"),
    ((512, 1), (128, 1), (), "avanzado", "ruta larga pero clara"),
    ((256, 2), (), (), "avanzado", "repeticion de meta alta"),
    ((128, 2), (256, 1), (), "avanzado", "mixto estable"),
    ((512, 1), (256, 1), (), "avanzado", "escalon grande"),
    ((256, 3), (), (), "avanzado", "tres doscientos cincuenta y seis"),
    ((512, 1), (128, 2), (), "dificil", "quinientos doce con apoyo"),
    ((256, 2), (128, 1), (), "dificil", "presion media alta"),
    ((512, 2), (), (), "dificil", "doble quinientos doce"),
    ((128, 3), (256, 1), (), "dificil", "densidad creciente"),
    ((512, 1), (256, 1), (), "dificil", "dos alturas distintas"),
    ((256, 3), (), (), "dificil", "sostener varias cadenas"),
    ((1024, 1), (), (), "dificil", "primer mil veinticuatro"),
    ((512, 1), (128, 2), (), "dificil", "mil con soporte indirecto"),
    ((256, 2), (512, 1), (), "dificil", "alternancia alta"),
    ((1024, 1), (128, 1), (), "dificil", "meta pico con ancla chica"),
    ((512, 2), (128, 1), (), "muy dificil", "doble quinientos doce y apoyo"),
    ((256, 3), (128, 1), (), "muy dificil", "densidad alta pero ordenable"),
    ((1024, 1), (256, 1), (), "muy dificil", "pico con escalon medio"),
    ((512, 1), (256, 2), (), "muy dificil", "mezcla exigente"),
    ((1024, 1), (512, 1), (), "muy dificil", "cima doble"),
    ((256, 4), (), (), "muy dificil", "volumen puro alto"),
    ((512, 2), (), (), "muy dificil", "doble quinientos doce limpio"),
    ((1024, 1), (256, 2), (), "muy dificil", "subida con apoyo real"),
    ((512, 1), (256, 3), (), "muy dificil", "densidad pesada"),
    ((1024, 2), (), (), "muy dificil", "doble mil veinticuatro"),
    ((256, 3), (512, 1), (), "extremo", "combinar ancho y pico"),
    ((1024, 1), (512, 1), (), "extremo", "ruta larga bien definida"),
    ((512, 2), (256, 1), (), "extremo", "doble alto con base"),
    ((1024, 1), (256, 2), (), "extremo", "meta alta con dos soportes"),
    ((2048, 1), (), (), "extremo", "primer dos mil cuarenta y ocho"),
    ((512, 3), (), (), "extremo", "triple quinientos doce"),
    ((1024, 2), (), (), "extremo", "doble mil veinticuatro otra vez"),
    ((2048, 1), (256, 1), (), "extremo", "pico final con apoyo chico"),
    ((512, 2), (1024, 1), (), "extremo", "antesala fuerte"),
    ((2048, 1), (512, 1), (), "extremo", "dos alturas muy separadas"),
    ((1024, 2), (256, 1), (), "experto", "doble mil con soporte"),
    ((512, 3), (256, 1), (), "experto", "volumen alto consistente"),
    ((2048, 1), (1024, 1), (), "experto", "cima y subcima"),
    ((1024, 2), (512, 1), (), "experto", "tres capas controladas"),
    ((2048, 1), (256, 2), (), "experto", "pico con base extendida"),
    ((1024, 3), (), (), "experto", "triple mil veinticuatro"),
    ((2048, 1), (512, 2), (), "experto", "final largo de control"),
    ((1024, 2), (256, 2), (), "experto", "cierre mixto"),
    ((2048, 1), (1024, 1), (512, 1), "maestro", "gran final por capas"),
    ((2048, 1), (1024, 2), (), "maestro", "cierre definitivo"),
]


def build_rows() -> list[dict[str, int | str]]:
    rows: list[dict[str, int | str]] = []
    for index, spec in enumerate(SPECS, start=1):
        goals_raw = spec[:3]
        difficulty = spec[3]
        note = spec[4]
        goals = [Goal(value=g[0], count=g[1]) for g in goals_raw if g]
        recommended = recommended_moves(goals)

        if index <= 10:
            buffer = 1
        elif index <= 30:
            buffer = 2
        elif index <= 70:
            buffer = 3
        else:
            buffer = 4

        moves = recommended + buffer

        normalized = list(goals_raw) + [(), (), ()]
        goal_1, goal_2, goal_3 = normalized[:3]
        rows.append(
            {
                "nivel": index,
                "movimientos": moves,
                "objetivo_1_valor": goal_1[0] if goal_1 else 0,
                "objetivo_1_cantidad": goal_1[1] if goal_1 else 0,
                "objetivo_2_valor": goal_2[0] if goal_2 else 0,
                "objetivo_2_cantidad": goal_2[1] if goal_2 else 0,
                "objetivo_3_valor": goal_3[0] if goal_3 else 0,
                "objetivo_3_cantidad": goal_3[1] if goal_3 else 0,
                "dificultad": difficulty,
                "nota_balance": note,
            }
        )
    return rows


def write_csv(rows: list[dict[str, int | str]], output_path: Path) -> None:
    fieldnames = [
        "nivel",
        "movimientos",
        "objetivo_1_valor",
        "objetivo_1_cantidad",
        "objetivo_2_valor",
        "objetivo_2_cantidad",
        "objetivo_3_valor",
        "objetivo_3_cantidad",
        "dificultad",
        "nota_balance",
    ]
    with output_path.open("w", encoding="utf-8", newline="") as target:
        writer = csv.DictWriter(target, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)


def main() -> int:
    root = Path(__file__).resolve().parents[1]
    output_path = root / "2048_retos_100_niveles_realistas.csv"
    rows = build_rows()
    write_csv(rows, output_path)
    print(f"CSV generado en: {output_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
