import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/imagiflow_api_client.dart';
import '../../core/theme/app_theme.dart';

typedef ResourceTitle = String Function(Map<String, dynamic> item);
typedef ResourceSubtitle = String Function(Map<String, dynamic> item);

class ResourceListScreen extends ConsumerStatefulWidget {
  const ResourceListScreen({super.key, required this.title, required this.path, required this.itemTitle, required this.itemSubtitle, this.fab});
  final String title;
  final String path;
  final ResourceTitle itemTitle;
  final ResourceSubtitle itemSubtitle;
  final Widget? fab;

  @override
  ConsumerState<ResourceListScreen> createState() => _ResourceListScreenState();
}

class _ResourceListScreenState extends ConsumerState<ResourceListScreen> {
  final _query = TextEditingController();
  Future<Map<String, dynamic>>? _future;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _query.dispose();
    super.dispose();
  }

  void _load() => setState(() => _future = ref.read(apiClientProvider).get(widget.path, query: {'page': 1, 'per_page': 30, if (_query.text.trim().isNotEmpty) 'q': _query.text.trim()}));

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text(widget.title)),
        floatingActionButton: widget.fab,
        body: Column(children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 8),
            child: TextField(
              controller: _query,
              decoration: InputDecoration(hintText: 'Pesquisar', prefixIcon: const Icon(Icons.search), suffixIcon: IconButton(icon: const Icon(Icons.clear), onPressed: () { _query.clear(); _load(); })),
              onSubmitted: (_) => _load(),
            ),
          ),
          Expanded(
            child: FutureBuilder<Map<String, dynamic>>(
              future: _future,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) return const Center(child: CircularProgressIndicator());
                if (snapshot.hasError) return _Failure(message: snapshot.error is ApiFailure ? (snapshot.error as ApiFailure).message : 'Não foi possível carregar os dados.', retry: _load);
                final items = ((snapshot.data?['items'] as List?) ?? const []).whereType<Map>().map((value) => value.cast<String, dynamic>()).toList();
                if (items.isEmpty) return const _EmptyState();
                return RefreshIndicator(
                  onRefresh: () async => _load(),
                  child: ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final item = items[index];
                      return Card(child: ListTile(title: Text(widget.itemTitle(item)), subtitle: Text(widget.itemSubtitle(item), maxLines: 2, overflow: TextOverflow.ellipsis), trailing: const Icon(Icons.chevron_right)));
                    },
                  ),
                );
              },
            ),
          ),
        ]),
      );
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();
  @override
  Widget build(BuildContext context) => const Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.inbox_outlined, size: 44, color: AppColors.muted), SizedBox(height: 12), Text('Nenhum registro encontrado.') ]));
}

class _Failure extends StatelessWidget {
  const _Failure({required this.message, required this.retry});
  final String message;
  final VoidCallback retry;
  @override
  Widget build(BuildContext context) => Center(child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [const Icon(Icons.cloud_off_outlined, size: 48, color: AppColors.muted), const SizedBox(height: 12), Text(message, textAlign: TextAlign.center), const SizedBox(height: 12), OutlinedButton(onPressed: retry, child: const Text('Tentar novamente'))])));
}
