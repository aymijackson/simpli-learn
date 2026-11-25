<?php

/*
 * "Finance & Fixed Assets in Thekan ERP" — content for ThekanServicesSeeder.
 *
 * Accounting fundamentals and controls, then the Finance module feature by
 * feature: setup (chart of accounts, mappings, bank & cash accounts,
 * periods, tax), journals, expenses, petty cash, payables and receivables,
 * banking and reconciliation, the fixed asset register, budgets, reports
 * and period/year close.
 *
 * Statuses and rules match Thekan ERP as built.
 *
 * Quiz format: 'q' => question, 'options' => [text => is_correct, ...].
 * More than one correct option makes it a "select all that apply" question.
 */

return [
    'test_prefix' => 'erp-finance',
    'test_label' => 'Finance',

    'course' => [
        'title' => 'Finance & Fixed Assets in Thekan ERP',
        'slug' => 'thekan-erp-finance',
        'description' => <<<'HTML'
<p>Every module in Thekan ERP ends up in Finance. A posted sales invoice, a supplier bill, a petty cash voucher, a depreciation run — each becomes a journal in the general ledger, and the ledger becomes the trial balance, the profit and loss, the balance sheet and the cash flow that management, the bank, auditors and the tax authority rely on.</p>
<p>This course teaches the accounting fundamentals and controls that well-run finance teams use worldwide, and the <strong>Finance</strong> module of Thekan ERP feature by feature: setting up the books, journals, expenses and petty cash, payables and receivables, banking and reconciliation, the fixed asset register and depreciation, budgets, reports, and month-end and year-end close.</p>
<ul>
<li><strong>Who it's for:</strong> accountants and finance officers, the ERP administrator, and managers who approve spending or read the financial reports.</li>
<li><strong>Before you start:</strong> complete <em>Thekan ERP Foundations</em>. This course opens once you've passed it.</li>
<li><strong>How it works:</strong> 7 modules, each ending with a test you must pass to unlock the next. The last module ends with the final assessment.</li>
<li><strong>To complete it:</strong> finish every lesson and score at least 80% on the final assessment. You'll receive a certificate.</li>
</ul>
HTML,
        'catalog' => [
            'subtitle' => 'Keep Thekan\'s books right — from the first journal to the year-end close.',
            'category' => 'Thekan ERP',
            'level' => 'intermediate',
            'duration_minutes' => 240,
            'instructor_name' => 'Thekan Services Limited',
            'instructor_bio' => 'Prepared by Thekan for its finance team and managers, based on recognised accounting practice (IFRS) and on how Thekan ERP is built.',
            'outcomes' => [
                'Apply double entry, accrual accounting and the core finance controls',
                'Set up the chart of accounts, mappings, bank accounts, periods and tax correctly',
                'Post, reverse and trace journals, expenses and petty cash with the right approvals',
                'Run payables and receivables, including three-way match, allocations and ageing',
                'Reconcile every bank account to zero difference',
                'Manage fixed assets from capitalisation through depreciation to disposal',
                'Budget, report and close the month and the year with confidence',
            ],
        ],
    ],

    'modules' => [

        // ------------------------------------------------------------------
        [
            'title' => 'Module 1: Accounting fundamentals and controls',
            'lessons' => [
                [
                    'title' => 'Double entry and the accounting equation',
                    'preview' => true,
                    'content' => <<<'HTML'
<p>Every accounting system in the world — from a paper cash book to Thekan ERP — rests on one idea: every transaction has two sides.</p>
<h1>The accounting equation</h1>
<blockquote><strong>Assets = Liabilities + Equity</strong></blockquote>
<ul>
<li><strong>Assets</strong> — what the business owns or is owed: cash and bank, receivables (customers who owe us), stock, machinery, vehicles.</li>
<li><strong>Liabilities</strong> — what the business owes: payables (suppliers we owe), VAT due to the tax authority, loans.</li>
<li><strong>Equity</strong> — what belongs to the owners: capital put in, plus profits kept in the business (retained earnings).</li>
</ul>
<p>Income increases equity and expenses reduce it, so the profit and loss is really a detailed story of how equity changed during the year.</p>
<h1>Debits and credits</h1>
<p>Every journal has at least two lines, and <strong>total debits always equal total credits</strong>. Which side increases an account depends on its type:</p>
<ul>
<li><strong>Debit increases</strong> assets, expenses and cost of sales.</li>
<li><strong>Credit increases</strong> liabilities, equity and income.</li>
</ul>
<p>This is called the account's <strong>normal balance</strong>. In Thekan ERP every account type records whether its normal balance is debit or credit.</p>
<h1>Worked examples</h1>
<ul>
<li>We pay ₦150,000 for diesel from the bank: <strong>Dr</strong> Fuel expense 150,000 / <strong>Cr</strong> Bank 150,000.</li>
<li>We invoice a client ₦2,000,000 plus 7.5% VAT: <strong>Dr</strong> Accounts receivable 2,150,000 / <strong>Cr</strong> Sales revenue 2,000,000 / <strong>Cr</strong> VAT output 150,000.</li>
<li>The client pays: <strong>Dr</strong> Bank 2,150,000 / <strong>Cr</strong> Accounts receivable 2,150,000.</li>
</ul>
<h1>Accrual accounting</h1>
<p>Under <strong>IFRS</strong> — the standards Nigerian companies report under — income is recorded when it is <strong>earned</strong> and expenses when they are <strong>incurred</strong>, not when cash moves. That is why we post an invoice when we deliver, and a supplier bill when we receive the goods, even though the money moves later. Receivables and payables are the bridge between the two moments.</p>
HTML,
                ],
                [
                    'title' => 'Controls every finance team needs',
                    'content' => <<<'HTML'
<p>Good accounting is not just correct arithmetic. It is a set of controls that make errors unlikely, fraud hard, and every figure provable.</p>
<h1>The core controls</h1>
<ul>
<li><strong>Segregation of duties</strong> — the person who creates a transaction should not be the one who approves or posts it, and the person who handles cash should not reconcile the bank. Where the team is small, a manager's review fills the gap.</li>
<li><strong>Evidence</strong> — every posting is backed by a document: an invoice, a receipt, a delivery note, a bank statement. No evidence, no posting.</li>
<li><strong>Authorisation</strong> — spending above agreed limits is approved before it happens, not explained afterwards.</li>
<li><strong>Posted means final</strong> — once a transaction is in the ledger it is never edited. Mistakes are corrected with a new, visible entry, so the history is never lost.</li>
<li><strong>Reconciliation</strong> — the system's figures are regularly proved against an outside source: bank statements, supplier statements, physical counts of cash, stock and assets.</li>
<li><strong>Period close</strong> — once a month is reviewed and reported, it is closed so nobody can change it.</li>
</ul>
<h1>Why it matters at Thekan</h1>
<ul>
<li>Clients pay deposits before we manufacture — that money must be tracked against each job.</li>
<li>Materials, subcontractors and site costs flow through many hands — controls stop leakage.</li>
<li>Banks, auditors and FIRS (the Federal Inland Revenue Service) will ask us to prove our numbers. A clean audit trail makes that quick.</li>
</ul>
<blockquote><strong>Rule of thumb:</strong> if you couldn't show an auditor the document behind a figure, the figure isn't finished.</blockquote>
HTML,
                ],
                [
                    'title' => 'How finance works in Thekan ERP',
                    'content' => <<<'HTML'
<p>Thekan ERP is a <strong>double-entry accounting engine</strong>. You rarely type debits and credits yourself — you create business documents, and posting them writes the journal for you.</p>
<h1>Source documents → ledger → reports</h1>
<ol>
<li><strong>Source documents</strong> — sales invoices and payments, supplier bills and payments, expenses, petty cash transactions, bank transactions, asset transactions.</li>
<li><strong>Validation</strong> — required fields, the right company, balanced totals and, for some documents, an open period.</li>
<li><strong>Posting</strong> — creates a journal entry (header) and its lines in the general ledger. Each journal records its <strong>source type</strong> and <strong>source ID</strong>, so you can always trace it back to the document that created it.</li>
<li><strong>Reports</strong> — the general ledger, trial balance, profit and loss, balance sheet and cash flow read <strong>posted journals only</strong>. Drafts never appear in the accounts.</li>
</ol>
<h1>The status model</h1>
<ul>
<li><strong>Draft</strong> — prepared, not yet in the accounts. Can be edited or deleted.</li>
<li><strong>Posted</strong> — in the ledger. Can't be edited.</li>
<li><strong>Reversed</strong> — a reversing journal has been posted against it.</li>
<li><strong>Voided</strong> — cancelled.</li>
</ul>
<p>Most finance documents follow the same pattern: <strong>draft → posted</strong>, with <strong>void</strong> to cancel a posted document. Petty cash and budgets add an approval step, which you'll see later.</p>
<h1>Where to find it</h1>
<p>Everything is under <strong>Finance</strong> in the sidebar: <em>Setup</em> (accounts, periods, tax), <em>Operations</em> (journals, invoices, payments, expenses, petty cash), <em>Fixed Assets</em>, <em>Payables (AP)</em>, <em>Banking</em>, <em>Planning</em> (budgets), <em>Receivables (AR)</em> and <em>Reports</em>. The menu items you see depend on your role's permissions.</p>
<p><strong>Finance SOP &amp; Docs</strong> in the menu is the finance team's written procedure — posting rules, reconciliation steps, checklists and a RACI — and can be downloaded as a PDF for audit files.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What is the accounting equation?', 'options' => [
                    'Assets = Liabilities + Equity' => true,
                    'Assets = Income − Expenses' => false,
                    'Liabilities = Assets + Equity' => false,
                    'Cash = Profit' => false,
                ]],
                ['q' => 'We pay ₦150,000 for diesel from the bank. What is the journal?', 'options' => [
                    'Dr Fuel expense / Cr Bank' => true,
                    'Dr Bank / Cr Fuel expense' => false,
                    'Dr Accounts payable / Cr Fuel expense' => false,
                    'Dr Fuel expense / Cr Sales revenue' => false,
                ]],
                ['q' => 'Under accrual accounting, when is a sale recorded?', 'options' => [
                    'When it is earned — for example on delivery — even if cash comes later' => true,
                    'Only when the cash arrives' => false,
                    'At the end of the year' => false,
                    'When the quote is sent' => false,
                ]],
                ['q' => 'Select ALL the core finance controls.', 'options' => [
                    'Segregation of duties' => true,
                    'Evidence for every posting' => true,
                    'Regular reconciliation' => true,
                    'Editing posted entries to fix mistakes' => false,
                ]],
                ['q' => 'Which journals do Thekan ERP\'s financial reports read?', 'options' => [
                    'Posted journals only' => true,
                    'Drafts and posted journals' => false,
                    'Only manual journals' => false,
                    'Whatever was entered most recently' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 2: Setting up the books',
            'lessons' => [
                [
                    'title' => 'Account types and the chart of accounts',
                    'content' => <<<'HTML'
<p>The <strong>chart of accounts</strong> (COA) is the list of every account the business posts to. A well-designed chart makes reports meaningful; a messy one makes them useless.</p>
<h1>Account types</h1>
<p><strong>Finance → Setup → Account Types</strong> defines the families of accounts. Each type has a code, a name, a <strong>category</strong> and a <strong>normal balance</strong> (debit or credit). The categories are:</p>
<ul>
<li><strong>Asset</strong> and <strong>liability</strong> and <strong>equity</strong> — the balance sheet.</li>
<li><strong>Income</strong>, <strong>cost of sales (COGS)</strong> and <strong>expense</strong> — the profit and loss.</li>
</ul>
<p>Reports use the category to decide where an account appears, so getting it right matters.</p>
<h1>The chart of accounts</h1>
<p><strong>Finance → Setup → Chart of Accounts</strong>. Every account needs an <strong>account type</strong>, a <strong>code</strong> and a <strong>name</strong>, and can sit under a <strong>parent</strong> account to build a hierarchy (for example <em>Operating expenses → Vehicle expenses → Fuel</em>).</p>
<h1>Good practice</h1>
<ul>
<li><strong>Use a numbering scheme</strong> — for example 1xxx assets, 2xxx liabilities, 3xxx equity, 4xxx income, 5xxx cost of sales, 6xxx expenses. Codes then sort sensibly in every report.</li>
<li><strong>Design for the questions management asks.</strong> Thekan needs to see furniture sales separately from construction, and workshop costs separately from site costs.</li>
<li><strong>Don't over-split.</strong> Detail by job belongs in projects and cost centres, not in hundreds of GL accounts.</li>
<li><strong>Don't delete or reuse accounts that have history</strong> — make them inactive instead. The Finance Health Check flags inactive accounts still being used.</li>
<li><strong>One owner.</strong> Only the finance manager or ERP administrator adds or changes accounts.</li>
</ul>
<h1>Starting from scratch</h1>
<p><strong>Fin. Data Init.</strong> (under <em>Data Setup</em>) can preview and then create a starter set: standard account types, a starter chart of accounts, account mappings and company settings. It's a one-time starting point for a new company — review and adapt everything it creates.</p>
HTML,
                ],
                [
                    'title' => 'Account mappings, finance settings and tax',
                    'content' => <<<'HTML'
<p>When you post an invoice, Thekan ERP has to know <em>which</em> receivable, revenue and VAT accounts to use. That's what mappings and settings are for.</p>
<h1>Account mappings</h1>
<p><strong>Finance → Setup → Account Mappings</strong> tells the posting engine which account to use for each role:</p>
<ul>
<li><strong>Required:</strong> accounts receivable (AR), accounts payable (AP) and the default bank GL account.</li>
<li><strong>Also set:</strong> sales revenue, cost of sales, inventory asset, work in progress, finished goods, retained earnings, sales and purchase discounts, rounding, <strong>VAT output</strong> and <strong>VAT input</strong>.</li>
</ul>
<p>If a mapping is missing, posting stops with a message saying so — for example, an expense with VAT can't post until the VAT input account is mapped.</p>
<h1>VAT settings (company settings)</h1>
<p><strong>VAT Settings</strong> holds the company-level accounts used for closing and control: <strong>retained earnings</strong>, <strong>income summary</strong> (both required to close the year), the <strong>AR</strong> and <strong>AP control</strong> accounts, the default cash account and the VAT output account.</p>
<h1>Tax rates and tax codes</h1>
<ul>
<li><strong>Tax Rates</strong> — the percentage, with a code, a type (<strong>VAT</strong> or <strong>sales tax</strong>), optional <strong>effective from/to</strong> dates, and whether it's compound.</li>
<li><strong>Tax Codes</strong> — what users pick on documents. A code links to a rate and can be marked <strong>exempt</strong>, <strong>out of scope</strong> or <strong>reverse charge</strong>.</li>
</ul>
<p>In Nigeria the standard VAT rate is <strong>7.5%</strong>. Some goods and services are exempt or zero-rated — check with Thekan's tax adviser before creating a code, and when rates change, end-date the old rate rather than editing it, so past documents keep their history.</p>
<h1>Exchange rates</h1>
<p><strong>Exchange Rates</strong> stores rates for foreign-currency documents — for example imported hardware billed in dollars. Documents carry a currency code and an exchange rate; record the rate from a consistent source on the transaction date.</p>
HTML,
                ],
                [
                    'title' => 'Bank & cash accounts, fiscal periods and the health check',
                    'content' => <<<'HTML'
<h1>Bank &amp; cash accounts</h1>
<p><strong>Finance → Setup → Bank &amp; Cash Acc.</strong> lists every place money is held. Each has a <strong>name</strong> and a <strong>type</strong> — <strong>bank</strong>, <strong>cash</strong>, <strong>wallet</strong> or <strong>mobile money</strong> — plus bank details, currency and an opening balance.</p>
<ul>
<li>Each must be <strong>linked to its GL account</strong>. Expenses, supplier payments and bank transactions post to that GL account; without the link, posting fails.</li>
<li>Mark one as the <strong>default</strong> so it's pre-selected on documents.</li>
<li>Keep one ERP account per real account. Never share one ERP account between two real bank accounts — you won't be able to reconcile either.</li>
</ul>
<h1>Fiscal periods</h1>
<p><strong>Fiscal Periods</strong> divides the year into periods (usually months). A period is <strong>open</strong> until it's <strong>closed</strong> after the month-end review.</p>
<ul>
<li>Posting a <strong>sales invoice</strong> or a <strong>bank transaction</strong> dated in a closed period is refused.</li>
<li>A period can't be closed while journals inside it are still <strong>unposted</strong>.</li>
<li>A closed period can be <strong>reopened</strong> by someone with permission — rarely, with a documented reason.</li>
</ul>
<p>Not every document checks the period yet, so the discipline is yours too: <strong>never date a new entry into a closed period</strong>. Post corrections in the current open period.</p>
<h1>Finance Health Check</h1>
<p><strong>Fin. Health Check</strong> (under <em>Data Setup</em>) scans the setup and the ledger and lists problems to fix, including:</p>
<ul>
<li>Missing core mappings or company settings.</li>
<li>Unbalanced journals, or journal lines pointing at missing accounts.</li>
<li>Bank accounts without a valid GL link.</li>
<li>Duplicate account codes, accounts without a type, invalid parents, inactive accounts still in use.</li>
<li>Fixed asset categories or assets missing accounts, and depreciation runs without a journal or without lines.</li>
</ul>
<blockquote><strong>Habit:</strong> run the health check after any setup change, and as the first step of every month-end close.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Which categories make up the profit and loss?', 'options' => [
                    'Income, cost of sales and expense' => true,
                    'Asset, liability and equity' => false,
                    'Bank and cash only' => false,
                    'Retained earnings only' => false,
                ]],
                ['q' => 'An account with history is no longer needed. What should you do?', 'options' => [
                    'Make it inactive' => true,
                    'Delete it' => false,
                    'Rename it for a different purpose' => false,
                    'Move its balance by editing old journals' => false,
                ]],
                ['q' => 'An expense with VAT won\'t post because the VAT input account is missing. Where do you fix it?', 'options' => [
                    'Account Mappings' => true,
                    'Exchange Rates' => false,
                    'Fiscal Periods' => false,
                    'Budgets' => false,
                ]],
                ['q' => 'What must every bank & cash account be linked to before you post to it?', 'options' => [
                    'Its GL account' => true,
                    'A budget' => false,
                    'A fixed asset' => false,
                    'A customer' => false,
                ]],
                ['q' => 'Select ALL true statements about fiscal periods.', 'options' => [
                    'Sales invoices and bank transactions dated in a closed period are refused' => true,
                    'A period can\'t be closed while journals in it are unposted' => true,
                    'Corrections should be posted in the current open period' => true,
                    'Closing a period deletes its journals' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 3: Journals, expenses and petty cash',
            'lessons' => [
                [
                    'title' => 'Journal entries',
                    'content' => <<<'HTML'
<p>Most journals are created automatically by posting documents. <strong>Manual journals</strong> are for everything else: accruals and prepayments, reclassifying a wrong account, opening balances, and adjustments agreed with the auditors.</p>
<h1>Creating a journal</h1>
<p><strong>Finance → Operations → Journal Entries</strong>. Enter the <strong>entry date</strong>, a <strong>reference</strong> and a <strong>memo</strong> explaining why, then the lines:</p>
<ul>
<li>At least <strong>two lines</strong>, each with an <strong>account</strong>.</li>
<li>Each line has a debit <strong>or</strong> a credit — never both.</li>
<li>Lines can carry a description, currency and rate, a party (customer or supplier) and, for bank lines, the <strong>bank account</strong> — needed for reconciliation.</li>
<li>Total debits must equal total credits and be greater than zero.</li>
</ul>
<p>New journals are saved as <strong>draft</strong>. Drafts can be edited or deleted.</p>
<h1>Posting, reversing and voiding</h1>
<ul>
<li><strong>Post</strong> — only drafts can be posted, and only if balanced. Posting records who posted and when, and the entry is then locked.</li>
<li><strong>Reverse</strong> — for a posted entry that's wrong. Thekan ERP creates a new posted journal with the debits and credits swapped, dated today and referenced <em>Reversal of …</em>, and marks the original <strong>reversed</strong>. Then post the correct entry.</li>
<li><strong>Void</strong> — cancels an entry. Use it for drafts you will never post. For a posted mistake, reverse it so the correction is visible in the ledger.</li>
</ul>
<h1>Good practice</h1>
<ul>
<li>Every manual journal needs a memo that a stranger would understand, and attached evidence or a reference to it.</li>
<li>One person prepares, another reviews and posts.</li>
<li>Never use a manual journal to post what should be a document (an invoice, a bill, a payment) — you'd lose the subledger, the ageing and the audit trail.</li>
<li>Admins can see an audit column in the list showing who created and posted each entry.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Expenses and expense categories',
                    'content' => <<<'HTML'
<p>Expenses record day-to-day spending that doesn't come through a purchase order: fuel, transport, utilities, bank-paid repairs, subscriptions.</p>
<h1>Expense categories</h1>
<p><strong>Expense Categories</strong> group spending for reporting and control — for example <em>Site transport</em>, <em>Workshop consumables</em>, <em>Office utilities</em>. Every expense needs one.</p>
<h1>Recording an expense</h1>
<p><strong>Finance → Operations → Expenses</strong>. Enter the <strong>date</strong>, <strong>category</strong>, optional supplier and reference, and choose the <strong>payment mode</strong>:</p>
<ul>
<li><strong>Bank</strong> or <strong>cash</strong> — paid now. Choose the bank or cash account it was paid from.</li>
<li><strong>Credit</strong> — to be paid later. Choose the payable (control) account.</li>
</ul>
<p>Then add at least one line: the <strong>GL account</strong> charged, <strong>quantity</strong>, <strong>unit cost</strong> and any <strong>tax</strong>.</p>
<h1>What posting does</h1>
<p>Expenses start as <strong>draft</strong> (editable and deletable). <strong>Post</strong> creates the journal:</p>
<ul>
<li><strong>Dr</strong> each line's expense account, and <strong>Dr</strong> VAT input for any tax.</li>
<li><strong>Cr</strong> the bank or cash account (paid now) or the payable account (credit).</li>
</ul>
<p>Posting is refused if a bank-paid expense has no bank account, a credit expense has no payable account, or tax is charged but VAT input isn't mapped. A posted expense can be <strong>voided</strong>, which also voids its journal.</p>
<h1>Good practice</h1>
<ul>
<li><strong>No receipt, no expense.</strong> Attach or file the receipt for every one.</li>
<li>Use the account that describes what was bought, not who bought it.</li>
<li>Supplier purchases with a PO go through <strong>Supplier Bills</strong>, not expenses, so three-way match can check them.</li>
<li>Small cash spending goes through <strong>Petty Cash</strong> — next lesson.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Petty cash',
                    'content' => <<<'HTML'
<p>Sites and the workshop need small cash for urgent items — a few screws, transport, a quick repair. Petty cash makes that possible without losing control.</p>
<h1>The imprest system</h1>
<p>Best practice worldwide is the <strong>imprest</strong> (float) system: the custodian holds a fixed float, say ₦100,000. Every payment is supported by a voucher and receipt. When cash runs low, the float is <strong>replenished</strong> by exactly what was spent. At any moment, <strong>cash in the box + vouchers = the float</strong>.</p>
<h1>Petty cash accounts</h1>
<p><strong>Finance → Operations → Petty Cash Accounts</strong>. Each has a code and name, its <strong>GL cash account</strong>, an expense clearing account, the <strong>float amount</strong>, an optional <strong>minimum balance</strong> (the ERP suggests replenishing when the balance falls to it) and a status: draft, active, inactive or closed. Only <strong>active</strong> accounts can be used.</p>
<h1>Transactions</h1>
<p>Types: <strong>funding</strong>, <strong>expense</strong>, <strong>replenishment</strong>, <strong>refund</strong>, <strong>adjustment</strong> and <strong>retirement</strong>.</p>
<ul>
<li>An <strong>expense</strong> needs an expense account and a payee (employee, supplier, customer or other), and can't exceed the cash available.</li>
<li>Save as <strong>draft</strong>, or <strong>pending</strong> to send for approval.</li>
<li>An approver with petty cash approval permission <strong>approves</strong> or <strong>rejects</strong> pending transactions.</li>
<li>Only <strong>approved</strong> transactions can be <strong>posted</strong>. Posting writes the journal — for an expense, <strong>Dr</strong> the expense account / <strong>Cr</strong> petty cash. Posted transactions can't be edited or deleted.</li>
<li><strong>Quick replenish</strong> on an active account creates the replenishment transaction for you. Each transaction can be printed as a <strong>voucher</strong>.</li>
</ul>
<h1>Petty cash reconciliation</h1>
<p>Count the cash regularly — at least monthly, and whenever the custodian changes. <strong>Petty Cash Reconciliation</strong> records the <strong>counted closing balance</strong> against the system balance. It is submitted, then approved (or rejected and corrected), then posted. If there's a <strong>variance</strong>, posting creates a journal for it, so shortages and overages are visible — and must be explained.</p>
<p><strong>Petty Cash Audit</strong> shows the trail of who did what.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'A posted journal used the wrong account. What do you do?', 'options' => [
                    'Reverse it, then post the correct entry' => true,
                    'Edit the posted journal' => false,
                    'Delete it' => false,
                    'Leave it and adjust at year end' => false,
                ]],
                ['q' => 'Which of these will Thekan ERP refuse to post as a manual journal?', 'options' => [
                    'Debits of ₦50,000 and credits of ₦45,000' => true,
                    'Two balanced lines of ₦50,000' => false,
                    'Three lines that balance' => false,
                    'A balanced entry with a memo' => false,
                ]],
                ['q' => 'An expense is paid later on credit. What must it have to post?', 'options' => [
                    'A payable (control) account' => true,
                    'A bank account' => false,
                    'A fixed asset' => false,
                    'A budget' => false,
                ]],
                ['q' => 'Under the imprest system, what is always true?', 'options' => [
                    'Cash in the box plus vouchers equals the float' => true,
                    'The float grows every month' => false,
                    'Receipts are optional' => false,
                    'Replenishments are rounded up' => false,
                ]],
                ['q' => 'Which petty cash transactions can be posted?', 'options' => [
                    'Approved transactions only' => true,
                    'Drafts' => false,
                    'Pending transactions' => false,
                    'Rejected transactions' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 4: Payables and receivables',
            'lessons' => [
                [
                    'title' => 'Supplier bills and three-way match',
                    'content' => <<<'HTML'
<p>Accounts payable (AP) is what Thekan owes its suppliers. The goal: pay every genuine bill once, on time, for exactly what we ordered and received — and nothing else.</p>
<h1>Three-way match</h1>
<p>Global best practice is to match three documents before a bill is approved:</p>
<ol>
<li>The <strong>purchase order</strong> — what we agreed to buy, at what price.</li>
<li>The <strong>goods received note</strong> — what actually arrived.</li>
<li>The <strong>supplier's invoice</strong> — what they're charging.</li>
</ol>
<h1>Supplier bills in Thekan ERP</h1>
<p><strong>Finance → Payables (AP) → Supplier Bills</strong>. A bill can be <strong>loaded from a source</strong> — a purchase requisition, RFQ, supplier quotation, purchase order or goods receipt — which brings in the supplier and lines. Enter the bill date, supplier reference and due date; each line has a GL account, quantity, unit cost and tax.</p>
<ul>
<li>Bills start as <strong>draft</strong>; only drafts can be edited or deleted.</li>
<li><strong>Match check</strong> shows the three-way match result before you post.</li>
<li><strong>Post</strong> creates the journal: <strong>Dr</strong> expense or asset (and VAT input) / <strong>Cr</strong> accounts payable. The total must be greater than zero.</li>
</ul>
<h1>What the match checks</h1>
<p>For every bill line linked to a PO line (directly or through a goods receipt), posting is <strong>refused</strong> if:</p>
<ul>
<li>the total billed would exceed the quantity <strong>received</strong>;</li>
<li>the total billed would exceed the quantity <strong>ordered</strong>; or</li>
<li>the unit cost differs from the PO price by more than <strong>2%</strong>.</li>
</ul>
<p>Lines with no PO or goods receipt behind them aren't matched — which is why purchases should go through procurement wherever possible. When a bill posts, the PO lines' billed quantities update, and costs linked to projects roll up into project costs.</p>
<p>A wrong bill can be <strong>voided</strong>. If the supplier owes us money back, use a supplier credit instead.</p>
HTML,
                ],
                [
                    'title' => 'Paying suppliers, credits and statements',
                    'content' => <<<'HTML'
<h1>Supplier payments</h1>
<p><strong>Supplier Payments</strong> records money paid to a supplier. Enter the date, supplier, <strong>bank account</strong> paid from and the amount, then <strong>allocate</strong> it to the bills it pays.</p>
<ul>
<li>Every payment needs at least one allocation, and the payment total must equal the sum of allocations.</li>
<li>You can only allocate to <strong>posted</strong> bills of that supplier with a balance due — and never more than the balance due.</li>
<li>Posting creates <strong>Dr</strong> accounts payable / <strong>Cr</strong> bank and reduces each bill's balance due. Part-payments are fine: the bill keeps its remaining balance.</li>
<li>Payments go draft → posted, and a posted payment can be voided.</li>
</ul>
<h1>Supplier credits</h1>
<p>When a supplier gives us a credit note — returned goods, an overcharge, a discount — record a <strong>Supplier Credit</strong> with its lines and <strong>apply</strong> it to the supplier's open bills. The amount applied can't exceed the credit total. Credits also go draft → posted → voided.</p>
<h1>Statements and ageing</h1>
<ul>
<li><strong>Supplier Statements</strong> — everything billed, paid and credited for a supplier. Reconcile it against the statement the supplier sends you at least monthly; differences usually mean a missing bill, a duplicate or a payment posted to the wrong supplier.</li>
<li><strong>AP Ageing</strong> — what we owe, grouped by how overdue it is. Use it to plan payments and protect supplier relationships.</li>
</ul>
<h1>Payment controls</h1>
<ul>
<li>Pay only posted, matched bills — never from a photocopy or a phone call.</li>
<li>Bank-detail changes from a supplier must be confirmed by phone on a known number. "We've changed our bank" emails are a classic fraud.</li>
<li>The person who prepares a payment is not the person who authorises it at the bank.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Receivables: invoices, payments, ageing and statements',
                    'content' => <<<'HTML'
<p>Accounts receivable (AR) is money customers owe Thekan. For a business that builds to order, cash collection is survival.</p>
<h1>Invoices and payments</h1>
<p><strong>Invoices</strong> and <strong>Payments</strong> in the Finance menu open the Sales module's documents (covered in <em>Sales &amp; Estimating in Thekan ERP</em>). From the finance side, remember:</p>
<ul>
<li>Posting an invoice creates <strong>Dr</strong> accounts receivable / <strong>Cr</strong> sales revenue and VAT output. It's refused if the invoice date is in a closed period or mappings are missing.</li>
<li>A posted invoice is never edited — corrections go through a <strong>credit note</strong>.</li>
<li>Posting a customer payment creates <strong>Dr</strong> bank / <strong>Cr</strong> accounts receivable.</li>
</ul>
<h1>AR Ageing</h1>
<p><strong>Finance → Receivables (AR) → AR Ageing</strong> shows each customer's balance due, grouped into <strong>0–30</strong>, <strong>31–60</strong>, <strong>61–90</strong> and <strong>over 90 days</strong> past due, and lets you open the invoices behind each figure.</p>
<h1>Customer Statements</h1>
<p><strong>Customer Statements</strong> lists a customer's invoices, payments and balance. Send one with every reminder — it ends most disputes quickly.</p>
<h1>Credit control good practice</h1>
<ul>
<li><strong>Deposits first.</strong> Bespoke work doesn't start without the agreed upfront payment.</li>
<li><strong>Invoice promptly</strong> at each milestone — delivery, installation, completion.</li>
<li><strong>Chase on a timetable</strong> — a friendly reminder before the due date, a call at 7 days overdue, escalation at 30.</li>
<li><strong>Review ageing weekly.</strong> The older a debt, the less likely it is to be paid.</li>
<li><strong>Retentions</strong> on construction contracts are real receivables — track their release dates.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Which three documents does a three-way match compare?', 'options' => [
                    'Purchase order, goods received note and supplier invoice' => true,
                    'Quote, sales order and delivery' => false,
                    'Budget, journal and trial balance' => false,
                    'Payslip, timesheet and contract' => false,
                ]],
                ['q' => 'A bill line for 10 doors is linked to a PO line where only 8 were received. What happens on posting?', 'options' => [
                    'Posting is refused — billed would exceed received' => true,
                    'It posts and pays 10' => false,
                    'It posts 8 and drops 2 silently' => false,
                    'The PO is cancelled' => false,
                ]],
                ['q' => 'What must be true for a supplier payment to post?', 'options' => [
                    'Its total equals the sum of its allocations to posted bills' => true,
                    'It pays every open bill in full' => false,
                    'It has no allocations' => false,
                    'It\'s dated next year' => false,
                ]],
                ['q' => 'A supplier emails saying their bank account has changed. What do you do?', 'options' => [
                    'Confirm by phone on a known number before changing anything' => true,
                    'Update the details straight away' => false,
                    'Reply asking them to confirm by email' => false,
                    'Pay both accounts to be safe' => false,
                ]],
                ['q' => 'Select ALL the age buckets in Thekan ERP\'s AR Ageing.', 'options' => [
                    '0–30 days' => true,
                    '31–60 days' => true,
                    '61–90 days' => true,
                    '91–365 days only' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 5: Banking and reconciliation',
            'lessons' => [
                [
                    'title' => 'Bank transactions',
                    'content' => <<<'HTML'
<p>Most money in and out is recorded through invoices, bills, payments and expenses. <strong>Bank transactions</strong> cover the rest: bank charges, interest, transfers between our own accounts, loan movements and other bank-originated items.</p>
<h1>Recording a bank transaction</h1>
<p><strong>Finance → Banking → Bank Transactions</strong>. Enter the <strong>date</strong>, <strong>type</strong> and <strong>bank account</strong>:</p>
<ul>
<li><strong>Deposit</strong> — money in. Add split lines saying which accounts it's credited to.</li>
<li><strong>Withdrawal</strong> — money out. Split lines say what it was for (for example bank charges).</li>
<li><strong>Transfer</strong> — between two of our accounts. Choose the destination account; it can't be the same as the source.</li>
</ul>
<p>Deposits and withdrawals need at least one split line, each with an account and an amount of at least 0.01.</p>
<h1>Post and unpost</h1>
<ul>
<li><strong>Post</strong> creates the journal, with the bank-side line tagged to the bank account so it can be reconciled. Posting is refused if the date is in a closed period.</li>
<li>A posted transaction can't be edited or deleted. If it's wrong, <strong>unpost</strong> it first, correct it and post again.</li>
</ul>
<h1>Good practice</h1>
<ul>
<li>Record bank charges and interest from the statement as soon as it arrives — then they're already there when you reconcile.</li>
<li>Always use <strong>transfer</strong> for movements between our own accounts, never a withdrawal from one and a separate deposit to the other.</li>
<li>Don't record customer receipts or supplier payments as bank transactions — use Payments and Supplier Payments, or the customer and supplier balances will be wrong.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Bank reconciliation',
                    'content' => <<<'HTML'
<p>Bank reconciliation proves that what the ledger says is in the bank is what the bank says is in the bank. It's the single most important control over cash: it catches missed entries, duplicates, bank errors and fraud.</p>
<h1>Why the two differ</h1>
<ul>
<li><strong>Timing</strong> — a cheque we wrote hasn't cleared; a deposit we recorded hasn't been credited yet.</li>
<li><strong>Items only the bank knows</strong> — charges, interest, direct debits, a customer's transfer we haven't recorded.</li>
<li><strong>Errors</strong> — wrong amount, wrong account, posted twice.</li>
</ul>
<h1>Reconciling in Thekan ERP</h1>
<p><strong>Finance → Banking → Bank Reconciliation</strong>:</p>
<ol>
<li><strong>Create</strong> a reconciliation: bank account, statement <strong>period start</strong> and <strong>end</strong>, and the statement's <strong>opening</strong> and <strong>closing balance</strong>.</li>
<li><strong>Import</strong> the statement lines from a CSV with columns <strong>date</strong>, <strong>description</strong> and <strong>amount</strong> (money in positive, money out negative). Dates can be YYYY-MM-DD or DD/MM/YYYY.</li>
<li><strong>Match</strong> each statement line to the posted journal line for that bank account. The ERP <strong>suggests matches</strong>; one statement line matches one journal line, and a journal line that's already cleared can't be matched again.</li>
<li>For items only on the statement, such as charges and interest, <strong>create an adjustment</strong> from the statement line — it posts the journal and matches it in one step.</li>
<li><strong>Exclude</strong> a statement line that doesn't belong (with a reason), or <strong>unmatch</strong> a wrong match.</li>
<li><strong>Close</strong> the reconciliation. It closes only when the statement closing balance equals the system's balance for that bank account at the period end, within 0.01.</li>
</ol>
<p>A closed reconciliation is read-only. If something must change, <strong>undo close</strong> reopens it.</p>
<h1>If it won't close</h1>
<ul>
<li>Is the bank account's opening balance in the ERP correct?</li>
<li>Are all receipts and payments for the period posted — with the right bank account and date?</li>
<li>Are charges and interest recorded?</li>
<li>Anything posted twice, or to the wrong bank account?</li>
</ul>
<blockquote><strong>Never force it.</strong> A difference is information. Find it, fix it at source, and reconcile every account every month.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'How should you record moving money from one Thekan bank account to another?', 'options' => [
                    'As a transfer bank transaction' => true,
                    'As a withdrawal and a separate deposit' => false,
                    'As an expense' => false,
                    'As a supplier payment' => false,
                ]],
                ['q' => 'A posted bank transaction has the wrong amount. What do you do?', 'options' => [
                    'Unpost it, correct it and post again' => true,
                    'Edit it while posted' => false,
                    'Delete it while posted' => false,
                    'Ignore it until year end' => false,
                ]],
                ['q' => 'Which columns must the statement CSV have?', 'options' => [
                    'date, description and amount' => true,
                    'name and email' => false,
                    'debit only' => false,
                    'account code and budget' => false,
                ]],
                ['q' => 'When can a bank reconciliation be closed?', 'options' => [
                    'When the statement closing balance equals the system balance at period end' => true,
                    'Whenever the month ends' => false,
                    'When half the lines are matched' => false,
                    'After any adjustment is posted' => false,
                ]],
                ['q' => 'Select ALL common reasons the bank and ledger differ.', 'options' => [
                    'Payments that haven\'t cleared yet' => true,
                    'Bank charges not yet recorded' => true,
                    'An entry posted twice' => true,
                    'The chart of accounts numbering scheme' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 6: Fixed assets',
            'lessons' => [
                [
                    'title' => 'Fixed assets — the principles',
                    'content' => <<<'HTML'
<p>Thekan's CNC machines, edge banders, panel saws, compressors, vehicles, generators, scaffolding and computers are <strong>fixed assets</strong> — property, plant and equipment used for more than one year. They're governed by <strong>IAS 16</strong> (property, plant and equipment) and <strong>IAS 36</strong> (impairment).</p>
<h1>Capitalise or expense?</h1>
<ul>
<li><strong>Capitalise</strong> (record as an asset) when an item will be used for more than a year and costs more than Thekan's capitalisation threshold. Its cost is spread over its life through depreciation.</li>
<li><strong>Expense</strong> small items, consumables and ordinary repairs and servicing immediately.</li>
<li>Cost includes everything needed to get the asset working: purchase price, import duty, delivery, installation. Refundable VAT is not part of cost.</li>
</ul>
<h1>Depreciation</h1>
<p>Depreciation spreads the cost, less the expected <strong>residual (salvage) value</strong>, over the <strong>useful life</strong>:</p>
<ul>
<li><strong>Straight-line</strong> — equal amounts each period. A ₦12,000,000 edge bander with ₦1,200,000 residual value and a 10-year (120-month) life depreciates by ₦90,000 a month.</li>
<li><strong>Declining (reducing) balance</strong> — a fixed percentage of the remaining value, so more in early years.</li>
</ul>
<p>Depreciation starts when the asset is <strong>available for use</strong> (in service), not when it's paid for. <strong>Net book value</strong> = cost − accumulated depreciation.</p>
<h1>Later life events</h1>
<ul>
<li><strong>Impairment</strong> — if an asset is damaged or obsolete so it's worth less than its book value, write it down.</li>
<li><strong>Revaluation</strong> — some companies carry land and buildings at fair value; increases normally go to a revaluation reserve.</li>
<li><strong>Disposal</strong> — on sale or scrapping, remove cost and accumulated depreciation; the difference between proceeds and net book value is a <strong>gain or loss</strong>.</li>
</ul>
<h1>Physical control</h1>
<p>Tag every asset with its code, record where it is and who's responsible, and count the register against the floor at least once a year. A register that doesn't match reality is worse than none.</p>
HTML,
                ],
                [
                    'title' => 'Categories, the asset register and capitalisation',
                    'content' => <<<'HTML'
<h1>Asset categories</h1>
<p><strong>Finance → Fixed Assets → Asset Categories</strong> sets the defaults for each class — for example <em>Workshop machinery</em>, <em>Motor vehicles</em>, <em>Computer equipment</em>, <em>Site equipment</em>:</p>
<ul>
<li>Default <strong>asset</strong>, <strong>accumulated depreciation</strong> and <strong>depreciation expense</strong> accounts.</li>
<li>Default <strong>disposal gain</strong> and <strong>disposal loss</strong> accounts.</li>
<li>Default <strong>method</strong> (straight-line or declining balance), <strong>useful life in months</strong> and <strong>salvage value</strong>.</li>
</ul>
<p>Set categories up carefully once, and every asset in them inherits the right accounting.</p>
<h1>Adding an asset</h1>
<p><strong>Fixed Assets</strong>: enter the category, a unique <strong>asset code</strong> (the tag number), name and description, <strong>purchase date</strong>, <strong>in-service date</strong>, <strong>purchase cost</strong>, salvage value, depreciation method, <strong>useful life (months)</strong>, the accounts, and details such as location, serial number, supplier and invoice number.</p>
<ul>
<li>New assets are <strong>draft</strong>. Only drafts can be edited or deleted.</li>
<li><strong>Activate</strong> puts the asset into service. It's refused unless the asset, accumulated depreciation and depreciation expense accounts are set, and disposal gain and loss accounts are set on the asset or its category.</li>
<li>Only <strong>active</strong> assets depreciate, transfer, revalue, impair, write off or dispose.</li>
</ul>
<h1>Capitalisation queue</h1>
<p><strong>Capitalisation Queue</strong> holds items waiting to become assets — for example a machine bought on a supplier bill. Each entry records its source (module, document and ID), category, asset name, quantity, unit cost and purchase date, and starts <strong>pending</strong>. <strong>Convert</strong> creates an active fixed asset from it; <strong>void</strong> (with a reason) removes it from the queue.</p>
<h1>Components</h1>
<p>Where parts of a big asset wear out at different rates — say the spindle motor of a CNC router — record them as <strong>Asset Components</strong> of the parent asset, each with its own cost, life and method. Components go draft → active → retired.</p>
HTML,
                ],
                [
                    'title' => 'Depreciation runs',
                    'content' => <<<'HTML'
<p>Depreciation is posted in <strong>runs</strong>, normally once a month as part of the month-end close.</p>
<h1>Running depreciation</h1>
<p><strong>Finance → Fixed Assets → Depreciation</strong>:</p>
<ol>
<li><strong>Preview</strong> — enter the <strong>period start</strong>, <strong>period end</strong> and <strong>run date</strong> to see the charge for every active asset before anything is saved.</li>
<li><strong>Run</strong> — creates a <strong>draft</strong> run with one line per asset. Only one run can exist per period; a second is refused.</li>
<li><strong>Review</strong> the draft — new assets included? disposed ones excluded? anything unusual?</li>
<li><strong>Post</strong> — writes the journal: <strong>Dr</strong> depreciation expense / <strong>Cr</strong> accumulated depreciation.</li>
</ol>
<h1>How the charge is calculated</h1>
<ul>
<li>Monthly charge = (cost − salvage value) ÷ useful life in months, for each month the asset was in service during the period.</li>
<li>Depreciation stops once the asset is fully depreciated down to its salvage value.</li>
<li>Thekan ERP currently calculates <strong>straight-line</strong> for every asset, whatever method is recorded on it.</li>
</ul>
<h1>Correcting a run</h1>
<p>A posted run can be <strong>voided</strong> with a reason; the ERP posts a reversing journal. Then run the period again.</p>
<h1>Good practice</h1>
<ul>
<li>Run depreciation every month, in order. Don't skip months and catch up later.</li>
<li>Before running, activate every asset that came into service in the period and dispose of anything sold or scrapped.</li>
<li>After posting, check that the depreciation expense in the profit and loss matches the run total.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Transfers, maintenance, revaluation, impairment, write-off and disposal',
                    'content' => <<<'HTML'
<p>Every life event goes through the same flow: create as <strong>draft</strong>, review, <strong>post</strong>. Posted events can be <strong>voided</strong> with a reason, and those that posted a journal get a reversing journal.</p>
<h1>Transfers</h1>
<p><strong>Asset Transfers</strong> record a move between <strong>locations</strong> or <strong>departments</strong> — say a generator moved from the workshop to a site. Transfers are non-financial: posting updates the register but creates no journal. Voiding a transfer doesn't move the asset back in the register, so correct the location yourself.</p>
<h1>Maintenance logs</h1>
<p><strong>Maintenance Logs</strong> record each service: date, type (<strong>preventive</strong>, <strong>corrective</strong>, <strong>inspection</strong>, <strong>calibration</strong> or <strong>warranty</strong>), cost and expense account. They build the asset's service history — use them to plan servicing and decide when to replace. Pay the supplier for the work through a supplier bill or an expense as usual; posting a maintenance log isn't switched on in Thekan's setup yet.</p>
<h1>Revaluations</h1>
<p>Enter the <strong>new cost</strong>, the method (<strong>reserve</strong> or <strong>P&amp;L</strong>) and the revaluation account. Posting adjusts the asset account and posts the difference to the revaluation account. Revaluations need a professional valuation behind them.</p>
<h1>Impairments</h1>
<p>Enter the <strong>impairment amount</strong> and the impairment expense account — for example after fire or flood damage, or when a machine is made obsolete. Posting charges the expense and reduces the asset's cost.</p>
<h1>Write-offs</h1>
<p>For an asset scrapped, lost or stolen with no proceeds. Posting removes the cost and accumulated depreciation and charges the net book value to the <strong>disposal loss</strong> account. The asset becomes <strong>written off</strong>. Voiding restores it to active.</p>
<h1>Disposals</h1>
<p>For an asset sold, use an <strong>asset transaction</strong> of type <strong>disposal</strong> on the asset, with the proceeds and the counter account (bank or receivable). Posting removes the cost and accumulated depreciation, records the proceeds, and posts the <strong>gain or loss</strong> (proceeds − net book value). The asset becomes <strong>disposed</strong>. (The other asset transaction type, <strong>acquisition</strong>, records an addition against a counter account such as bank, AP or clearing.)</p>
<h1>FA Reports</h1>
<ul>
<li><strong>Register</strong> — every asset with cost, depreciation and net book value.</li>
<li><strong>Depreciation</strong> — charges by period.</li>
<li><strong>Movements</strong> — additions, disposals and other events.</li>
<li><strong>Forecast</strong> — future depreciation, for budgets.</li>
</ul>
<p>Each report can be exported as a PDF for the auditors.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'A ₦12,000,000 edge bander has a ₦1,200,000 salvage value and a 120-month life. What is the monthly straight-line depreciation?', 'options' => [
                    '₦90,000' => true,
                    '₦100,000' => false,
                    '₦120,000' => false,
                    '₦10,000' => false,
                ]],
                ['q' => 'When does depreciation start?', 'options' => [
                    'When the asset is in service (available for use)' => true,
                    'When the purchase order is raised' => false,
                    'When the supplier is paid in full' => false,
                    'At the end of the financial year' => false,
                ]],
                ['q' => 'What must be set before an asset can be activated?', 'options' => [
                    'Asset, accumulated depreciation and depreciation expense accounts, plus disposal gain and loss accounts' => true,
                    'A budget' => false,
                    'A customer' => false,
                    'A bank reconciliation' => false,
                ]],
                ['q' => 'An asset with net book value ₦800,000 is sold for ₦1,000,000. What does disposal post?', 'options' => [
                    'A gain of ₦200,000' => true,
                    'A loss of ₦200,000' => false,
                    'Revenue of ₦1,000,000' => false,
                    'Nothing — disposals are non-financial' => false,
                ]],
                ['q' => 'Select ALL the asset events that post a journal when posted.', 'options' => [
                    'Impairment' => true,
                    'Write-off' => true,
                    'Disposal' => true,
                    'Transfer between locations' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 7: Budgets, reports and closing the books',
            'lessons' => [
                [
                    'title' => 'Budgets and budget vs actual',
                    'content' => <<<'HTML'
<p>A budget turns Thekan's plans into numbers — and then shows, month by month, whether we're on track.</p>
<h1>Creating a budget</h1>
<p><strong>Finance → Planning → Budgets</strong>. Enter a <strong>name</strong>, <strong>start</strong> and <strong>end date</strong>, the <strong>period type</strong> (<strong>monthly</strong>, <strong>quarterly</strong> or <strong>annual</strong>), currency and notes. Add the accounts to budget — usually the income, cost of sales and expense accounts — and fill in the amounts in the <strong>grid</strong>, one cell per account per period.</p>
<h1>Draft → approved → locked</h1>
<ul>
<li><strong>Draft</strong> — being prepared.</li>
<li><strong>Approved</strong> — only drafts can be approved.</li>
<li><strong>Locked</strong> — only approved budgets can be locked. A locked budget can't be changed: no grid edits, no accounts added or removed.</li>
</ul>
<h1>Budget vs actual</h1>
<p>The budget report compares each account against <strong>posted</strong> journals in the budget's dates:</p>
<ul>
<li><strong>Expense</strong> variance = budget − actual. Positive is favourable (we spent less).</li>
<li><strong>Income</strong> variance = actual − budget. Positive is favourable (we earned more).</li>
</ul>
<h1>Good practice</h1>
<ul>
<li>Every budget line has an owner who explains its variances.</li>
<li>Write the assumptions down in the notes — "two new site vans from Q2", "diesel at ₦1,200/litre".</li>
<li>Investigate big variances in both directions. An underspend can mean work not done or a bill not posted.</li>
<li>Lock the approved budget. Re-forecast separately rather than rewriting the original.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Financial reports',
                    'content' => <<<'HTML'
<p>All reports are under <strong>Finance → Reports</strong> and read posted journals only. Every figure can be drilled down: report → account → journal lines → journal → source document.</p>
<h1>The reports</h1>
<ul>
<li><strong>General Ledger</strong> — every posted line for an account over a date range, with running balance. Use it to answer "what makes up this number?"</li>
<li><strong>Trial Balance</strong> — every account's debit or credit balance. Total debits must equal total credits. It's the first report to check at month-end and can be exported to CSV.</li>
<li><strong>Profit &amp; Loss</strong> — income, cost of sales and expenses for a period: did we make money?</li>
<li><strong>Balance Sheet</strong> — assets, liabilities and equity at a date: what do we own and owe?</li>
<li><strong>Cash Flow</strong> — where cash came from and went: operating, investing (such as machinery) and financing (such as loans).</li>
</ul>
<h1>Reading them well</h1>
<ul>
<li><strong>Profit isn't cash.</strong> A profitable month with large unpaid invoices can still leave the bank empty — read the P&amp;L, balance sheet and cash flow together.</li>
<li><strong>Gross margin</strong> (sales − cost of sales) tells you whether jobs are priced right. Compare it month to month and against the estimates.</li>
<li><strong>Check the balance sheet accounts</strong> — bank should match the reconciliation, AR the ageing, AP the supplier statements, fixed assets the register.</li>
<li><strong>Odd balances are clues</strong> — a credit balance on an expense account, or a debit on a revenue account, usually means a posting error.</li>
</ul>
<p>Management dashboards (such as the CFO dashboard) summarise the same ledger for quick review.</p>
HTML,
                ],
                [
                    'title' => 'Month-end and year-end close',
                    'content' => <<<'HTML'
<p>Closing the books turns a month of transactions into numbers management can trust — and then locks them.</p>
<h1>The month-end checklist</h1>
<ol>
<li>All sales invoices, customer payments and credit notes for the month are <strong>posted</strong>.</li>
<li>All supplier bills, supplier payments, expenses and petty cash are <strong>posted</strong>. Chase missing bills for goods received.</li>
<li><strong>Petty cash</strong> counted and reconciled.</li>
<li>Every <strong>bank account reconciled</strong> and closed at zero difference.</li>
<li><strong>Depreciation run</strong> posted for the month.</li>
<li><strong>Accruals and prepayments</strong> posted by journal, with memos.</li>
<li><strong>Finance Health Check</strong> run and issues fixed.</li>
<li><strong>Trial balance</strong> reviewed; P&amp;L variances against budget explained; balance sheet accounts proved against the ageing, statements and register.</li>
<li>The <strong>fiscal period closed</strong>.</li>
</ol>
<p>Remember that a period won't close while journals in it are unposted — post or delete the drafts first.</p>
<h1>Year-end close</h1>
<p><strong>Finance → Setup → Year Close</strong> closes the financial year. Before it will run:</p>
<ul>
<li>The <strong>retained earnings</strong> and <strong>income summary</strong> accounts must be set in VAT (company) settings.</li>
<li>There must be no unposted journals in the year.</li>
<li>The year must not already be closed.</li>
</ul>
<p>Year close posts the closing journal that moves the year's net profit (or loss) through income summary into <strong>retained earnings</strong>, and closes the year and its periods. Income and expense accounts then start the new year at zero, while balance sheet accounts carry forward.</p>
<h1>Before you close the year</h1>
<ul>
<li>Complete the December month-end in full.</li>
<li>Count stock and fixed assets and agree them to the ledger.</li>
<li>Agree year-end adjustments with the auditors.</li>
<li>Export and file the final trial balance, P&amp;L, balance sheet and FA register.</li>
</ul>
<p>Mark this lesson complete, then take the final assessment: 25 questions in 40 minutes. You need 80% to pass, with up to 3 attempts.</p>
HTML,
                ],
            ],
            'final' => [
                'title' => 'Finance & Fixed Assets — Final Assessment',
                'slug' => 'erp-finance-assessment',
                'description' => '<p>The final assessment for Finance &amp; Fixed Assets in Thekan ERP, covering accounting fundamentals, controls and every finance process in the ERP.</p>',
                'instructions' => <<<'HTML'
<p>This assessment has <strong>25 questions</strong> and you have <strong>40 minutes</strong>.</p>
<ul>
<li>You need <strong>80%</strong> to pass. You have up to <strong>3 attempts</strong>.</li>
<li>Some questions ask you to <strong>select all that apply</strong> — choose every correct option, and only those, to get the mark.</li>
<li>Questions appear in random order. You can move between them and flag ones to come back to.</li>
<li>This assessment is monitored: leaving the exam window or copying text is recorded. Work on your own.</li>
</ul>
HTML,
                'duration_minutes' => 40,
                'pass_percentage' => 80,
                'max_attempts' => 3,
                'questions' => [
                    ['q' => 'Which side increases an expense account?', 'options' => [
                        'Debit' => true,
                        'Credit' => false,
                        'Either, it doesn\'t matter' => false,
                        'Neither — expenses never change' => false,
                    ]],
                    ['q' => 'We invoice a client ₦2,000,000 plus 7.5% VAT. What is credited to VAT output?', 'options' => [
                        '₦150,000' => true,
                        '₦2,150,000' => false,
                        '₦75,000' => false,
                        'Nothing — VAT is debited' => false,
                    ]],
                    ['q' => 'Why should the person who creates a transaction not also approve it?', 'options' => [
                        'Segregation of duties catches errors and makes fraud harder' => true,
                        'It makes posting faster' => false,
                        'The ERP can\'t store two names' => false,
                        'It\'s required for VAT' => false,
                    ]],
                    ['q' => 'What does every journal\'s source type and source ID allow you to do?', 'options' => [
                        'Trace the journal back to the document that created it' => true,
                        'Edit the journal after posting' => false,
                        'Skip the period check' => false,
                        'Delete the source document' => false,
                    ]],
                    ['q' => 'Which three account mappings are required?', 'options' => [
                        'Accounts receivable, accounts payable and the default bank GL account' => true,
                        'Fuel, rent and salaries' => false,
                        'Retained earnings, budgets and assets' => false,
                        'None — mappings are optional' => false,
                    ]],
                    ['q' => 'The VAT rate changes. What should you do with the old tax rate?', 'options' => [
                        'End-date it and create a new rate, so past documents keep their history' => true,
                        'Edit the percentage on the existing rate' => false,
                        'Delete it' => false,
                        'Leave both active with no dates' => false,
                    ]],
                    ['q' => 'What is the Finance Health Check for?', 'options' => [
                        'Scanning setup and the ledger for problems such as missing mappings and unbalanced journals' => true,
                        'Checking staff medical records' => false,
                        'Approving budgets' => false,
                        'Posting depreciation' => false,
                    ]],
                    ['q' => 'A manual journal line has both a debit and a credit. What happens?', 'options' => [
                        'It\'s refused — a line has a debit or a credit, never both' => true,
                        'The ERP nets them off' => false,
                        'It posts as entered' => false,
                        'It becomes a reversal' => false,
                    ]],
                    ['q' => 'What does reversing a posted journal do in Thekan ERP?', 'options' => [
                        'Creates a new posted journal with debits and credits swapped and marks the original reversed' => true,
                        'Deletes the original' => false,
                        'Edits the original amounts' => false,
                        'Moves it back to draft' => false,
                    ]],
                    ['q' => 'A bank-paid expense with VAT is posted. Select ALL the lines its journal contains.', 'options' => [
                        'Dr the expense account' => true,
                        'Dr VAT input' => true,
                        'Cr the bank account' => true,
                        'Cr sales revenue' => false,
                    ]],
                    ['q' => 'A site needs ₦8,000 of screws urgently. What\'s the right route?', 'options' => [
                        'A petty cash expense with a receipt, approved then posted' => true,
                        'A manual journal' => false,
                        'A customer payment' => false,
                        'A fixed asset' => false,
                    ]],
                    ['q' => 'A petty cash count shows ₦2,000 less than the system. What happens to the difference?', 'options' => [
                        'The reconciliation is approved and posted, creating a variance journal — and it must be explained' => true,
                        'It\'s ignored' => false,
                        'The custodian edits the system balance' => false,
                        'It\'s added to next month\'s float' => false,
                    ]],
                    ['q' => 'A supplier bill\'s unit cost is 5% above the PO price. What happens on posting?', 'options' => [
                        'It\'s refused — the price differs from the PO by more than 2%' => true,
                        'It posts at the PO price' => false,
                        'It posts at the bill price without warning' => false,
                        'The PO price is updated' => false,
                    ]],
                    ['q' => 'What does posting a supplier payment do?', 'options' => [
                        'Dr accounts payable / Cr bank, and reduces each allocated bill\'s balance due' => true,
                        'Dr bank / Cr accounts payable' => false,
                        'Deletes the paid bills' => false,
                        'Creates a new supplier bill' => false,
                    ]],
                    ['q' => 'Where do you see which customers are more than 90 days overdue?', 'options' => [
                        'AR Ageing' => true,
                        'Chart of Accounts' => false,
                        'Depreciation' => false,
                        'Tax Codes' => false,
                    ]],
                    ['q' => 'A customer\'s transfer arrives in the bank. How should it be recorded?', 'options' => [
                        'As a customer payment against their invoice' => true,
                        'As a bank deposit to sales revenue' => false,
                        'As a manual journal to the bank' => false,
                        'As a supplier credit' => false,
                    ]],
                    ['q' => 'A bank charge is on the statement but not in the ERP. During reconciliation you should…', 'options' => [
                        'Create an adjustment from the statement line, which posts and matches it' => true,
                        'Exclude it' => false,
                        'Change the statement closing balance' => false,
                        'Close the reconciliation anyway' => false,
                    ]],
                    ['q' => 'Select ALL true statements about bank reconciliation in Thekan ERP.', 'options' => [
                        'One statement line matches one journal line' => true,
                        'A closed reconciliation is read-only until undo close' => true,
                        'It closes only when the difference is zero (within 0.01)' => true,
                        'Draft journals can be matched' => false,
                    ]],
                    ['q' => 'Which item should be capitalised as a fixed asset?', 'options' => [
                        'A new panel saw that will be used for years' => true,
                        'A box of screws' => false,
                        'Routine servicing of a vehicle' => false,
                        'A month\'s diesel' => false,
                    ]],
                    ['q' => 'What does posting a depreciation run do?', 'options' => [
                        'Dr depreciation expense / Cr accumulated depreciation' => true,
                        'Dr bank / Cr fixed assets' => false,
                        'Dr fixed assets / Cr depreciation expense' => false,
                        'Nothing — runs are reports only' => false,
                    ]],
                    ['q' => 'A depreciation run already exists for this month. What happens if you try to create another?', 'options' => [
                        'It\'s refused — one run per period' => true,
                        'It doubles the charge' => false,
                        'It replaces the first silently' => false,
                        'It runs for next month instead' => false,
                    ]],
                    ['q' => 'A generator moves from the workshop to a site. What do you record?', 'options' => [
                        'An asset transfer' => true,
                        'A disposal' => false,
                        'A write-off' => false,
                        'A new asset' => false,
                    ]],
                    ['q' => 'Which budget can no longer be changed?', 'options' => [
                        'A locked budget' => true,
                        'A draft budget' => false,
                        'An approved budget' => false,
                        'Any budget with notes' => false,
                    ]],
                    ['q' => 'Select ALL steps of the month-end close.', 'options' => [
                        'Reconcile every bank account' => true,
                        'Post the depreciation run' => true,
                        'Review the trial balance and close the period' => true,
                        'Delete posted journals that look wrong' => false,
                    ]],
                    ['q' => 'What must be set before the year can be closed?', 'options' => [
                        'Retained earnings and income summary accounts' => true,
                        'A new chart of accounts' => false,
                        'Next year\'s budget' => false,
                        'A fixed asset category' => false,
                    ]],
                ],
            ],
        ],
    ],

    'recommended_for' => ['Accountant', 'ERP Administrator'],
];
