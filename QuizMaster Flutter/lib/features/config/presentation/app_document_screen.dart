import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';

import '../../../core/theme/app_theme.dart';
import '../models/app_document.dart';

class AppDocumentScreen extends StatelessWidget {
  const AppDocumentScreen({
    super.key,
    required this.future,
  });

  final Future<AppDocument> future;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.pageBackground(context),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: AppTheme.textColor(context),
      ),
      body: FutureBuilder<AppDocument>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  '${snapshot.error}',
                  textAlign: TextAlign.center,
                ),
              ),
            );
          }

          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final document = snapshot.data!;
          return Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppTheme.cardBackground(context),
                borderRadius: BorderRadius.circular(24),
                boxShadow: const [
                  BoxShadow(
                    color: Color(0x120E2741),
                    blurRadius: 18,
                    offset: Offset(0, 10),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: SingleChildScrollView(
                      child: document.content.isEmpty
                          ? Text(
                              'No content available.',
                              style: Theme.of(context)
                                  .textTheme
                                  .bodyMedium
                                  ?.copyWith(
                                    height: 1.55,
                                    color: AppTheme.mutedTextColor(context),
                                  ),
                            )
                          : Html(
                              data: document.content,
                              style: {
                                'body': Style(
                                  margin: Margins.zero,
                                  padding: HtmlPaddings.zero,
                                  fontSize: FontSize(15),
                                  lineHeight: const LineHeight(1.55),
                                ),
                                'p': Style(
                                  margin: Margins.only(bottom: 14),
                                ),
                                'h1': Style(
                                  fontWeight: FontWeight.w800,
                                  fontSize: FontSize(28),
                                  margin: Margins.only(bottom: 16),
                                ),
                                'h2': Style(
                                  fontWeight: FontWeight.w800,
                                  fontSize: FontSize(22),
                                  margin: Margins.only(bottom: 14),
                                ),
                                'h3': Style(
                                  fontWeight: FontWeight.w700,
                                  fontSize: FontSize(18),
                                  margin: Margins.only(bottom: 12),
                                ),
                                'ul': Style(
                                  margin: Margins.only(bottom: 16, left: 8),
                                  padding: HtmlPaddings.only(left: 16),
                                ),
                                'ol': Style(
                                  margin: Margins.only(bottom: 16, left: 8),
                                  padding: HtmlPaddings.only(left: 16),
                                ),
                                'li': Style(
                                  margin: Margins.only(bottom: 8),
                                ),
                                'strong': Style(
                                  fontWeight: FontWeight.w800,
                                ),
                                'a': Style(
                                  color: AppTheme.primary,
                                  textDecoration: TextDecoration.underline,
                                ),
                              },
                            ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
