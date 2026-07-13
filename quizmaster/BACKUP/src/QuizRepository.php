<?php

final class QuizRepository
{
    public function __construct(
        private Database $db,
        private string $domainUrl,
    ) {}

    public function getSystemConfigurations(): ?array
    {
        $row = $this->db->fetchOne(
            "SELECT message FROM settings WHERE type='system_configurations' LIMIT 1"
        );

        if ($row === null) {
            return null;
        }

        return json_decode($row['message'], true);
    }

    public function getCategories(int $type = 1, ?int $languageId = null): array
    {
        $conditions = ["c.type={$type}"];
        if ($languageId !== null) {
            $conditions[] = "c.language_id={$languageId}";
        }

        if ($type === 1) {
            $sql = "SELECT *,
                (SELECT COUNT(id) FROM question WHERE question.category = c.id) AS no_of_que,
                (SELECT COUNT(id) FROM subcategory s WHERE s.maincat_id = c.id AND s.status = 1) AS no_of,
                (SELECT language FROM languages l WHERE l.id = c.language_id) AS language,
                IF(
                    (SELECT COUNT(id) FROM subcategory s WHERE s.maincat_id = c.id AND s.status = 1) = 0,
                    (SELECT MAX(level + 0) FROM question q WHERE q.category = c.id),
                    0
                ) AS maxlevel
                FROM category c
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY CAST(c.row_order AS UNSIGNED) ASC";
        } elseif ($type === 2) {
            $sql = "SELECT *,
                (SELECT COUNT(id) FROM tbl_learning WHERE tbl_learning.category = c.id) AS no_of,
                (SELECT language FROM languages l WHERE l.id = c.language_id) AS language
                FROM category c
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY CAST(c.row_order AS UNSIGNED) ASC";
        } else {
            $sql = "SELECT *,
                (SELECT COUNT(id) FROM tbl_maths_question WHERE tbl_maths_question.category = c.id) AS no_of_que,
                (SELECT COUNT(id) FROM subcategory s WHERE s.maincat_id = c.id AND s.status = 1) AS no_of,
                (SELECT language FROM languages l WHERE l.id = c.language_id) AS language,
                0 AS maxlevel
                FROM category c
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY CAST(c.row_order AS UNSIGNED) ASC";
        }

        $rows = $this->db->fetchAll($sql);

        return array_map(function (array $row): array {
            return [
                'id' => $row['id'],
                'language_id' => $row['language_id'] ?? '',
                'category_name' => $row['category_name'] ?? '',
                'type' => $row['type'] ?? '1',
                'image' => $this->normalizeCategoryImage($row['image'] ?? ''),
                'plan' => '0',
                'amount' => '0',
                'row_order' => $row['row_order'] ?? '0',
                'no_of_que' => $row['no_of_que'] ?? '0',
                'language' => $row['language'] ?? '',
                'maxlevel' => $row['maxlevel'] ?? '0',
                'no_of' => $row['no_of'] ?? '0',
                'IsPurchased' => 'true',
            ];
        }, $rows);
    }

    public function getSubcategories(int $mainCategoryId): array
    {
        $parent = $this->db->fetchOne(
            "SELECT id, type FROM category WHERE id={$mainCategoryId} AND status='1' LIMIT 1"
        );

        if ($parent === null) {
            return [];
        }

        $type = (int) ($parent['type'] ?? 1);
        $extraFields = $type === 3
            ? ", (SELECT COUNT(id) FROM tbl_maths_question WHERE tbl_maths_question.subcategory = subcategory.id) AS no_of, 0 AS maxlevel"
            : ", (SELECT COUNT(id) FROM question WHERE question.subcategory = subcategory.id) AS no_of,
               (SELECT MAX(level + 0) FROM question WHERE question.subcategory = subcategory.id) AS maxlevel";

        $sql = "SELECT *{$extraFields}
            FROM subcategory
            WHERE maincat_id={$mainCategoryId} AND status=1
            ORDER BY CAST(row_order AS UNSIGNED) ASC";

        $rows = $this->db->fetchAll($sql);

        return array_map(function (array $row): array {
            return [
                'id' => $row['id'],
                'maincat_id' => $row['maincat_id'],
                'subcategory_name' => $row['subcategory_name'] ?? '',
                'image' => $this->normalizeSubcategoryImage($row['image'] ?? ''),
                'row_order' => $row['row_order'] ?? '0',
                'no_of' => $row['no_of'] ?? '0',
                'maxlevel' => $row['maxlevel'] ?? '0',
            ];
        }, $rows);
    }

    public function getQuestionsByCategory(int $categoryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT *
             FROM question
             WHERE category={$categoryId}
             ORDER BY id ASC"
        );

        return array_map(fn(array $row): array => $this->mapQuestion($row), $rows);
    }

    public function getQuestionsBySubcategory(int $subcategoryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT *
             FROM question
             WHERE subcategory={$subcategoryId}
             ORDER BY id ASC"
        );

        return array_map(fn(array $row): array => $this->mapQuestion($row), $rows);
    }

    private function mapQuestion(array $row): array
    {
        return [
            'id' => $row['id'] ?? '',
            'category' => $row['category'] ?? '0',
            'subcategory' => $row['subcategory'] ?? '0',
            'language_id' => $row['language_id'] ?? '0',
            'image' => $this->normalizeQuestionImage($row['image'] ?? ''),
            'question' => $row['question'] ?? '',
            'question_type' => $row['question_type'] ?? '1',
            'optiona' => $row['optiona'] ?? '',
            'optionb' => $row['optionb'] ?? '',
            'optionc' => $row['optionc'] ?? '',
            'optiond' => $row['optiond'] ?? '',
            'optione' => $row['optione'] ?? '',
            'answer' => $row['answer'] ?? '',
            'level' => $row['level'] ?? '0',
            'note' => $row['note'] ?? '',
        ];
    }

    private function normalizeCategoryImage(string $image): string
    {
        if ($image === '') {
            return '';
        }

        return $this->domainUrl . 'images/category/' . $image;
    }

    private function normalizeSubcategoryImage(string $image): string
    {
        if ($image === '') {
            return '';
        }

        return $this->domainUrl . 'images/subcategory/' . $image;
    }

    private function normalizeQuestionImage(string $image): string
    {
        if ($image === '') {
            return '';
        }
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        return $this->domainUrl . 'images/questions/' . $image;
    }
}
