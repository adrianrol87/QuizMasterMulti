import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../auth/models/app_user.dart';
import '../data/bookmark_repository.dart';

class BookmarksScreen extends StatefulWidget {
  const BookmarksScreen({
    super.key,
    required this.locale,
    required this.currentUser,
    required this.repository,
  });

  final Locale locale;
  final AppUser currentUser;
  final BookmarkRepository repository;

  @override
  State<BookmarksScreen> createState() => _BookmarksScreenState();
}

class _BookmarksScreenState extends State<BookmarksScreen> {
  late Future<List<BookmarkedQuestion>> _future;

  bool get _isSpanish => widget.locale.languageCode == 'es';

  @override
  void initState() {
    super.initState();
    _future = widget.repository.fetchBookmarks(widget.currentUser.id);
  }

  Future<void> _refresh() async {
    setState(() {
      _future = widget.repository.fetchBookmarks(widget.currentUser.id);
    });
    await _future;
  }

  Future<void> _remove(BookmarkedQuestion item) async {
    try {
      await widget.repository.setBookmark(
        userId: widget.currentUser.id,
        questionId: item.question.id,
        bookmarked: false,
      );
      if (!mounted) return;
      await _refresh();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _isSpanish ? 'Pregunta eliminada.' : 'Question removed.',
          ),
        ),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _isSpanish
                ? 'No se pudo eliminar. Intenta de nuevo.'
                : 'Could not remove it. Please try again.',
          ),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.pageBackground(context),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        foregroundColor: AppTheme.textColor(context),
        elevation: 0,
        title: Text(_isSpanish ? 'Preguntas guardadas' : 'Saved questions'),
      ),
      body: FutureBuilder<List<BookmarkedQuestion>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return _BookmarkMessage(
              icon: Icons.cloud_off_rounded,
              message: _isSpanish
                  ? 'No se pudieron cargar tus preguntas.'
                  : 'Your saved questions could not be loaded.',
              actionLabel: _isSpanish ? 'Reintentar' : 'Retry',
              onAction: _refresh,
            );
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final bookmarks = snapshot.data!;
          if (bookmarks.isEmpty) {
            return _BookmarkMessage(
              icon: Icons.bookmark_border_rounded,
              message: _isSpanish
                  ? 'Todavía no guardaste preguntas. Usa el marcador durante un Quiz.'
                  : 'You have not saved questions yet. Use the bookmark during a Quiz.',
            );
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(18, 8, 18, 30),
              itemCount: bookmarks.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final item = bookmarks[index];
                return _BookmarkCard(
                  item: item,
                  isSpanish: _isSpanish,
                  onRemove: () => _remove(item),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class _BookmarkCard extends StatelessWidget {
  const _BookmarkCard({
    required this.item,
    required this.isSpanish,
    required this.onRemove,
  });

  final BookmarkedQuestion item;
  final bool isSpanish;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    final question = item.question;
    final answer = question.correctAnswerText.trim().isNotEmpty
        ? question.correctAnswerText.trim()
        : question.answer.trim();

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        boxShadow: const [
          BoxShadow(
            color: Color(0x100E2741),
            blurRadius: 16,
            offset: Offset(0, 7),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  item.categoryName.trim().isEmpty
                      ? (isSpanish ? 'Quiz' : 'Quiz')
                      : item.categoryName,
                  style: const TextStyle(
                    color: Color(0xFF2B6FB6),
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              IconButton(
                tooltip: isSpanish ? 'Eliminar' : 'Remove',
                onPressed: onRemove,
                icon: const Icon(Icons.bookmark_remove_rounded),
                color: const Color(0xFFCB4C4C),
              ),
            ],
          ),
          if (question.imageUrl.trim().isNotEmpty) ...[
            const SizedBox(height: 8),
            ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Image.network(
                question.imageUrl,
                width: double.infinity,
                height: 150,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => const SizedBox.shrink(),
              ),
            ),
          ],
          const SizedBox(height: 8),
          Text(
            question.question,
            style: const TextStyle(
              color: AppTheme.ink,
              fontSize: 18,
              height: 1.3,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 14),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFEAF8F0),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Text(
              '${isSpanish ? 'Respuesta' : 'Answer'}: $answer',
              style: const TextStyle(
                color: Color(0xFF17683E),
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          if (question.note.trim().isNotEmpty) ...[
            const SizedBox(height: 10),
            Text(
              question.note,
              style: const TextStyle(
                color: Color(0xFF64758A),
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _BookmarkMessage extends StatelessWidget {
  const _BookmarkMessage({
    required this.icon,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  final IconData icon;
  final String message;
  final String? actionLabel;
  final Future<void> Function()? onAction;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 48, color: const Color(0xFF2B6FB6)),
            const SizedBox(height: 14),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: AppTheme.ink,
                fontSize: 17,
                fontWeight: FontWeight.w700,
              ),
            ),
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: 16),
              FilledButton(
                onPressed: onAction,
                child: Text(actionLabel!),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
