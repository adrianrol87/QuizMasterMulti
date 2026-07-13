class QuizSubcategory {
  const QuizSubcategory({
    required this.id,
    required this.mainCategoryId,
    required this.title,
    required this.imageUrl,
    required this.questionCount,
    required this.maxLevel,
    required this.rowOrder,
  });

  factory QuizSubcategory.fromApi(Map<String, dynamic> json) {
    return QuizSubcategory(
      id: json['id'].toString(),
      mainCategoryId: json['maincat_id'].toString(),
      title: (json['subcategory_name'] ?? '').toString(),
      imageUrl: (json['image'] ?? '').toString(),
      questionCount: int.tryParse((json['no_of'] ?? '0').toString()) ?? 0,
      maxLevel: int.tryParse((json['maxlevel'] ?? '0').toString()) ?? 0,
      rowOrder: int.tryParse((json['row_order'] ?? '0').toString()) ?? 0,
    );
  }

  final String id;
  final String mainCategoryId;
  final String title;
  final String imageUrl;
  final int questionCount;
  final int maxLevel;
  final int rowOrder;
}
