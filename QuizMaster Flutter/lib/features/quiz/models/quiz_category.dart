import 'package:flutter/material.dart';

class QuizCategory {
  const QuizCategory({
    required this.id,
    required this.languageId,
    required this.title,
    required this.questionCount,
    required this.subcategoryCount,
    required this.maxLevel,
    required this.plan,
    required this.amount,
    required this.isPurchased,
    required this.rowOrder,
    required this.imageUrl,
    required this.color,
    required this.icon,
  });

  factory QuizCategory.fromApi(
    Map<String, dynamic> json, {
    required Color color,
    required IconData icon,
  }) {
    return QuizCategory(
      id: json['id'].toString(),
      languageId: json['language_id'].toString(),
      title: (json['category_name'] ?? '').toString(),
      questionCount: int.tryParse((json['no_of_que'] ?? '0').toString()) ?? 0,
      subcategoryCount: int.tryParse((json['no_of'] ?? '0').toString()) ?? 0,
      maxLevel: int.tryParse((json['maxlevel'] ?? '0').toString()) ?? 0,
      plan: (json['plan'] ?? 'Free').toString(),
      amount: int.tryParse((json['amount'] ?? '0').toString()) ?? 0,
      isPurchased: (json['IsPurchased'] ?? 'false').toString() == 'true',
      rowOrder: int.tryParse((json['row_order'] ?? '0').toString()) ?? 0,
      imageUrl: (json['image'] ?? '').toString(),
      color: color,
      icon: icon,
    );
  }

  final String id;
  final String languageId;
  final String title;
  final int questionCount;
  final int subcategoryCount;
  final int maxLevel;
  final String plan;
  final int amount;
  final bool isPurchased;
  final int rowOrder;
  final String imageUrl;
  final Color color;
  final IconData icon;

  bool get isPremium => plan.trim().toLowerCase() == 'paid';
}
