# Code Review Notes

## Bugs / High-risk issues
- `TransactionManager::delete` trusts the incoming occurrence date and passes it straight into `Carbon::parse`, which will throw if the UI ever sends an invalid string (e.g., a tampered Livewire request). Add date validation/guarding before parsing to avoid fatal errors.
- `TransactionReport::monthlyWithRecurring` only checks for the `transactions` table before eager loading `category` and `occurrenceExceptions`. If migrations are partially run (e.g., categories not yet created), this will still try to join missing tables. Consider gating on all dependent tables or short-circuiting more defensively.
- Dashboard budget actuals are calculated over projected recurring transactions, so mid-month numbers include future occurrences. This can overstate "actual" spend and may confuse users comparing against budgets.

## Simplification opportunities
- The recurring-transaction deletion flow could explicitly branch between deleting a single occurrence vs. the entire series, rather than relying on a nullable `$occurrenceDate` argument.
- `TransactionReport::monthlyWithRecurring` can exit early when the schema is incomplete, avoiding the eager-load branch entirely.

## Readability & maintainability
- Add inline comments in `Transaction::projectedOccurrencesForMonth` to explain the recurrence window calculation (`$cycleEnd`) and how exceptions are applied, since the loop involves several guards.
- Consider naming the budget "actual" total something like `spentPennies` to distinguish it from the configured budget amount when mapping budget summaries.

## Code quality improvements
- Validate or cast user-provided dates before parsing them in Livewire actions to avoid runtime exceptions.
- Prefer wrapping the net worth save + line-item sync in a database transaction to keep summary rows and detail rows consistent if any insert fails.

## Status updates
- Addressed defensive parsing for recurring transaction deletions.
- Added schema guards for dependent tables in transaction reports.
- Budget "actual" calculations now ignore future projected occurrences for the current month and use clearer naming internally.
- Added clarifying recurrence comments and ensured net worth save/line-item writes run transactionally.
