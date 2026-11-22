import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../theme/app_colors.dart';

/// Bottom bar from the prototype: «الرئيسية» · «القضايا» · (+) · «الاستشارات» · «حسابي».
class AppShell extends StatelessWidget {
  const AppShell({super.key, required this.shell});
  final StatefulNavigationShell shell;

  @override
  Widget build(BuildContext context) {
    Widget tab(int index, IconData icon, String label) {
      final selected = shell.currentIndex == index;
      final color = selected ? AppColors.teal600 : AppColors.faint;
      return Expanded(
        child: InkResponse(
          onTap: () => shell.goBranch(index, initialLocation: index == shell.currentIndex),
          child: Semantics(
            selected: selected,
            button: true,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(icon, color: color, size: 23),
                const SizedBox(height: 2),
                Text(
                  label,
                  style: TextStyle(fontSize: 11, fontWeight: selected ? FontWeight.w700 : FontWeight.w500, color: color),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Scaffold(
      body: shell,
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/cases/new'),
        tooltip: 'قضية جديدة',
        backgroundColor: AppColors.teal600,
        foregroundColor: Colors.white,
        shape: const CircleBorder(),
        child: const Icon(LucideIcons.plus, size: 28),
      ),
      bottomNavigationBar: BottomAppBar(
        color: AppColors.surface,
        elevation: 8,
        height: 68,
        padding: EdgeInsets.zero,
        shape: const CircularNotchedRectangle(),
        child: Row(
          children: [
            tab(0, LucideIcons.house, 'الرئيسية'),
            tab(1, LucideIcons.briefcase, 'القضايا'),
            const SizedBox(width: 72),
            tab(2, LucideIcons.messageCircle, 'الاستشارات'),
            tab(3, LucideIcons.user, 'حسابي'),
          ],
        ),
      ),
    );
  }
}
