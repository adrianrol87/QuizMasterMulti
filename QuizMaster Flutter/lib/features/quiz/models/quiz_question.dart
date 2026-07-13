class QuizQuestion {
  const QuizQuestion({
    required this.id,
    required this.categoryId,
    required this.subcategoryId,
    required this.question,
    required this.questionType,
    required this.options,
    required this.answer,
    required this.correctOptionKey,
    required this.correctAnswerText,
    required this.level,
    required this.note,
    required this.imageUrl,
  });

  factory QuizQuestion.fromApi(Map<String, dynamic> json) {
    final options = <String>[
      (json['optiona'] ?? '').toString(),
      (json['optionb'] ?? '').toString(),
      (json['optionc'] ?? '').toString(),
      (json['optiond'] ?? '').toString(),
      (json['optione'] ?? '').toString(),
    ].where((value) => value.trim().isNotEmpty).toList();
    final rawAnswer = (json['answer'] ?? '').toString().trim();
    final normalizedAnswer = rawAnswer.toLowerCase();
    final optionKeys = ['a', 'b', 'c', 'd', 'e'];

    String resolvedKey = normalizedAnswer;
    if (!optionKeys.contains(resolvedKey)) {
      final optionIndex = options.indexWhere(
        (option) => option.trim().toLowerCase() == normalizedAnswer,
      );
      if (optionIndex >= 0 && optionIndex < optionKeys.length) {
        resolvedKey = optionKeys[optionIndex];
      }
    }

    String resolvedAnswerText = rawAnswer;
    final resolvedIndex = optionKeys.indexOf(resolvedKey);
    if (resolvedIndex >= 0 && resolvedIndex < options.length) {
      resolvedAnswerText = options[resolvedIndex];
    }

    return QuizQuestion(
      id: (json['id'] ?? '').toString(),
      categoryId: (json['category'] ?? '0').toString(),
      subcategoryId: (json['subcategory'] ?? '0').toString(),
      question: (json['question'] ?? '').toString(),
      questionType: int.tryParse((json['question_type'] ?? '1').toString()) ?? 1,
      options: options,
      answer: rawAnswer,
      correctOptionKey: resolvedKey,
      correctAnswerText: resolvedAnswerText,
      level: int.tryParse((json['level'] ?? '0').toString()) ?? 0,
      note: (json['note'] ?? '').toString(),
      imageUrl: (json['image'] ?? '').toString(),
    );
  }

  final String id;
  final String categoryId;
  final String subcategoryId;
  final String question;
  final int questionType;
  final List<String> options;
  final String answer;
  final String correctOptionKey;
  final String correctAnswerText;
  final int level;
  final String note;
  final String imageUrl;
}
