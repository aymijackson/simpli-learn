<?php

/*
 * "Thekan ERP Foundations" — content for ThekanServicesSeeder.
 *
 * The first course for everyone at Thekan: how an ERP should be used
 * (global practice), how work flows through Thekan's business, and the
 * screens every user shares — the menu, company switcher, dashboard,
 * search, tasks, notifications, chat, documents and the audit trail.
 * Module courses (Projects, Sales, Procurement…) build on it.
 *
 * Screen names, statuses and rules match Thekan ERP as built.
 *
 * Quiz format: 'q' => question, 'options' => [text => is_correct, ...].
 * More than one correct option makes it a "select all that apply" question.
 */

return [
    'test_prefix' => 'erp-foundations',
    'test_label' => 'ERP Foundations',

    'course' => [
        'title' => 'Thekan ERP Foundations',
        'slug' => 'thekan-erp-foundations',
        'description' => <<<'HTML'
<p>Thekan designs, manufactures and installs furniture, and builds and constructs. One job can touch almost everyone in the firm: the designer who draws it, the estimator who prices it, procurement who buys the boards and fittings, the workshop that makes it, the stores that hold it, the site team that installs it, and finance who bills and collects for it.</p>
<p><strong>Thekan ERP</strong> is where all of that work lives. This course teaches you how a well-run organisation uses an ERP, how work flows through Thekan's business, and the screens and habits everyone shares: the menu, your dashboard, search, tasks, approvals, notifications, chat and documents.</p>
<ul>
<li><strong>Who it's for:</strong> everyone who uses Thekan ERP. Take it first — the module courses (Projects, Sales, Procurement and more) build on it.</li>
<li><strong>How it works:</strong> 6 modules, each ending with a short test you must pass to unlock the next. The last module ends with the final assessment.</li>
<li><strong>To complete it:</strong> finish every lesson and score at least 80% on the final assessment. You'll receive a certificate.</li>
</ul>
HTML,
        'catalog' => [
            'subtitle' => 'How a world-class business runs on its ERP — and how to work in Thekan ERP every day.',
            'category' => 'Thekan ERP',
            'level' => 'beginner',
            'duration_minutes' => 150,
            'instructor_name' => 'Thekan Services Limited',
            'instructor_bio' => 'Prepared by Thekan for everyone who uses Thekan ERP, based on how the system is built and on recognised ERP practice.',
            'outcomes' => [
                'Explain why Thekan runs on one ERP and how a job flows from enquiry to cash',
                'Apply the golden rules of ERP data: enter once, at source, on time, accurately',
                'Find your way around: the menu, company switcher, dashboard and global search',
                'Run a task from start to sign-off, with checklists, blockers and dependencies',
                'Use notifications, chat and approvals correctly — and know which to use when',
                'Store, version and link documents with the right status and confidentiality',
                'Protect your account and understand permissions and the audit trail',
            ],
        ],
    ],

    'modules' => [

        // ------------------------------------------------------------------
        [
            'title' => 'Module 1: Why we run Thekan on one ERP',
            'lessons' => [
                [
                    'title' => 'What an ERP is, and what good looks like',
                    'preview' => true,
                    'content' => <<<'HTML'
<p>An <strong>ERP</strong> (Enterprise Resource Planning system) is one shared system that runs a company's core processes — selling, buying, making, storing, delivering, building, billing, paying and reporting — on one set of data.</p>
<p>Before an ERP, most firms run on spreadsheets, WhatsApp threads, paper job cards and people's memories. Each department keeps its own version of the truth, and nobody can answer simple questions quickly: <em>What did this job really cost? Which client owes us? Do we have enough 18mm board for next week's wardrobes?</em></p>
<h1>What the best companies do</h1>
<p>Organisations that get real value from their ERP — in every industry and every country — share the same habits:</p>
<ul>
<li><strong>One source of truth.</strong> If it isn't in the ERP, it didn't happen. No side spreadsheets that compete with the system.</li>
<li><strong>Process first.</strong> The system follows an agreed process (for example: requisition → approval → purchase order → goods receipt → supplier bill → payment). Everyone follows the same steps.</li>
<li><strong>Data owned by the people who create it.</strong> The storekeeper records the receipt, the site supervisor records the blocker, the project manager approves the timesheet. Nobody "types it up later" for someone else.</li>
<li><strong>Controls built in.</strong> Approvals, permissions and an audit trail, so the person who requests money is not the person who approves and pays it.</li>
<li><strong>Management by information.</strong> Leaders decide from dashboards and reports, not from chasing people for updates.</li>
</ul>
<blockquote>An ERP doesn't make a company efficient on its own. People using it the same way, every day, do. This course is about those habits.</blockquote>
HTML,
                ],
                [
                    'title' => 'How a Thekan job flows through the ERP',
                    'content' => <<<'HTML'
<p>Thekan has two kinds of work that often meet on the same job: <strong>furniture</strong> we design, manufacture and install, and <strong>building and construction</strong> we deliver on site. Here is how a typical job moves through Thekan ERP, and which module holds each step.</p>
<ol>
<li><strong>Enquiry and relationship — CRM.</strong> A lead is captured, qualified into an opportunity, and the client's company, contacts and sites are recorded.</li>
<li><strong>Pricing — Sales.</strong> The estimator prepares a Bill of Quantities (BoQ) and a Sales Quote, using the quote catalogue, templates and price lists.</li>
<li><strong>Winning the job — Sales and Projects.</strong> When the quote is <strong>won</strong>, a Project can be created from it, with a starting budget built from the quote's lines.</li>
<li><strong>Buying — Procurement.</strong> Requisitions, requests for quotation, supplier quotations, purchase orders and goods receipts bring in materials and services.</li>
<li><strong>Storing — Inventory.</strong> Stock entries, issues to jobs, transfers between stores and adjustments keep stock accurate.</li>
<li><strong>Making — Production.</strong> Bills of materials (BOMs), routings and work orders run the workshop, with tasks, time logs and costs per work order.</li>
<li><strong>Delivering and building — Projects.</strong> Tasks, milestones, timesheets, costs and budgets manage installation and construction on site.</li>
<li><strong>Billing and money — Sales, Projects and Finance.</strong> Invoices, payments, supplier bills, bank reconciliation and the accounts.</li>
<li><strong>People — HRM.</strong> Employees, contracts, attendance, leave, payroll, training and performance.</li>
<li><strong>Records — Document Management.</strong> Drawings, contracts, certificates and photos, versioned and linked to the records they belong to.</li>
</ol>
<p>Across all of these sit shared tools you'll use daily: <strong>Tasks</strong>, <strong>Notifications</strong>, <strong>Chat</strong>, <strong>Search</strong> and the <strong>Dashboards</strong>.</p>
<blockquote><strong>Why it matters to you:</strong> what you enter is what the next person works from. A wrong quantity on a goods receipt becomes a wrong stock level, a wrong job cost and a wrong invoice. Every step depends on the one before.</blockquote>
HTML,
                ],
                [
                    'title' => 'The golden rules of ERP data',
                    'content' => <<<'HTML'
<p>The quality of everything the ERP shows — stock levels, job costs, profit, overdue lists — depends on the quality of what we put in. These rules are global good practice, and they are how we work at Thekan.</p>
<h1>1. Enter it once, at the source</h1>
<p>The person closest to the event records it, in the ERP, directly. Don't write it on paper to type up later, and don't send it to someone else to enter for you.</p>
<h1>2. Enter it on time</h1>
<p>Record things when they happen — the same day at the latest. Late entries make dashboards lie: a goods receipt entered a week late means a week of "out of stock" warnings and possibly a duplicate order.</p>
<h1>3. Get it right</h1>
<ul>
<li>Choose the correct project, task, customer, supplier, product and unit of measure. Check before you save.</li>
<li>Use the reference fields (invoice numbers, delivery note numbers, drawing numbers) so others can trace the record.</li>
<li>Write descriptions someone else can understand: "Kitchen base units, Block B flat 4 — 6 no." not "units".</li>
</ul>
<h1>4. Don't create duplicates</h1>
<p>Before creating a customer, supplier, product or contractor, <strong>search first</strong>. Duplicate master records split history, confuse reports and cause payments to the wrong account.</p>
<h1>5. Use the process — no workarounds</h1>
<p>If the process says a purchase order must be approved before goods are received, don't receive goods against an unapproved order "just this once". If the system stops you, there is usually a control behind it; ask why before looking for a way around it.</p>
<h1>6. Fix mistakes the right way</h1>
<p>Don't delete and re-enter posted records to "clean up". Use the correction the process provides — a reversal, a credit note, a stock adjustment with a reason, a reschedule — so the history stays true.</p>
<blockquote><strong>Rule of thumb:</strong> if a colleague, an auditor or a client looked at your record in six months' time, would they understand exactly what happened?</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What does "one source of truth" mean for Thekan ERP?', 'options' => [
                    'If it isn\'t recorded in the ERP, it didn\'t happen — no competing side spreadsheets' => true,
                    'Each department keeps its own spreadsheet and the ERP is updated monthly' => false,
                    'Only managers enter data into the ERP' => false,
                    'The ERP is used only for finance' => false,
                ]],
                ['q' => 'When a sales quote is won, what can be created from it in Thekan ERP?', 'options' => [
                    'A Project, with a starting budget built from the quote\'s lines' => true,
                    'A payroll run' => false,
                    'A stock adjustment' => false,
                    'A new user account for the client' => false,
                ]],
                ['q' => 'Who should record a goods receipt in the ERP?', 'options' => [
                    'The person who received the goods, at the time they arrive' => true,
                    'The accountant, at the end of the month' => false,
                    'Anyone, whenever they have time' => false,
                    'The supplier' => false,
                ]],
                ['q' => 'Select ALL the golden rules of ERP data.', 'options' => [
                    'Enter it once, at the source' => true,
                    'Search before creating a customer, supplier or product' => true,
                    'Record events on time — the same day at the latest' => true,
                    'Delete and re-enter posted records to tidy them up' => false,
                ]],
                ['q' => 'You made a mistake on a posted record. What is the right approach?', 'options' => [
                    'Use the correction the process provides (reversal, credit note, adjustment with a reason) so history stays true' => true,
                    'Delete the record and create a new one' => false,
                    'Leave it and fix it in a spreadsheet' => false,
                    'Ask a colleague to log in as you and change it' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 2: Finding your way around',
            'lessons' => [
                [
                    'title' => 'Signing in, your profile and the menu',
                    'content' => <<<'HTML'
<h1>Signing in</h1>
<ul>
<li>Sign in with your own work email and password. Your account is personal — every action you take is recorded against your name.</li>
<li>Only tick <strong>Remember Me</strong> on your own work device, never on a shared or public computer.</li>
<li>Forgotten your password? Use <strong>Forgot password?</strong> on the sign-in page to get a reset link by email.</li>
<li>For security, you'll be signed out automatically after a period of inactivity.</li>
</ul>
<h1>Your profile</h1>
<p>Click your name or initials at the top right to open the user menu: <strong>Profile</strong>, <strong>Settings</strong>, <strong>Activity Log</strong>, <strong>Help &amp; Support</strong> and <strong>Logout</strong>. <strong>My Profile</strong> shows your account details and your role(s), and lets you choose the appearance (theme) of the system. Appearance changes apply instantly and save automatically.</p>
<h1>The menu (sidebar)</h1>
<p>The sidebar on the left lists the areas you can use, grouped by module: Dashboard, Chat, Dashboards, CRM, Sales, Procurement, Products, Stock Management, Production, Financials, HRM, Tasks, Projects &amp; Maintenance, Document Management, Locations, Master Data and more.</p>
<p><strong>You only see what your role allows.</strong> Menu items are controlled by permissions. If you need something you can't see, ask your manager to request the access from the ERP administrator — don't borrow someone else's login.</p>
<blockquote><strong>Tip:</strong> most modules have a guide in the menu — <em>Module Guide</em> (Projects), <em>Sales Guide</em>, <em>Procurement Guide</em>, <em>SOP &amp; Workflow</em> (Inventory), <em>CRM SOP &amp; Privileges</em> and <em>Finance SOP &amp; Docs</em>. They explain each module's process and controls, and can be downloaded as PDF.</blockquote>
HTML,
                ],
                [
                    'title' => 'Companies: choosing whose data you see',
                    'content' => <<<'HTML'
<p>Thekan ERP can hold more than one company. You may belong to one company or several, and a project can be shared between companies (for example, one company handles the furniture and another the construction).</p>
<h1>The company switcher</h1>
<p>At the top of the screen you'll see <strong>Viewing data for</strong> followed by a company name, or <strong>All Companies</strong>. This controls whose records you see in lists, dashboards and reports.</p>
<ul>
<li><strong>All Companies</strong> shows records from every company you belong to.</li>
<li>Choosing <strong>one company</strong> narrows everything to that company — useful when you're preparing figures for one company or want to avoid entering a record against the wrong one.</li>
<li>You can only ever see companies you belong to.</li>
</ul>
<h1>Why it matters</h1>
<ul>
<li>A record created while viewing the wrong company can end up in the wrong company's books, stock or projects.</li>
<li>If a list looks empty or "something is missing", check the company switcher first — you may be viewing a different company.</li>
</ul>
<blockquote><strong>Habit:</strong> glance at "Viewing data for" before you create anything important — a purchase order, an invoice, a project.</blockquote>
HTML,
                ],
                [
                    'title' => 'Your dashboard: what needs your attention',
                    'content' => <<<'HTML'
<p>The <strong>Dashboard</strong> is your home page. Instead of making you hunt through every module, it pulls together what needs <em>your</em> attention.</p>
<h1>Attention cards</h1>
<p>For each module you have access to, the dashboard shows what's waiting for you, for example:</p>
<ul>
<li><strong>Projects, Production and Tasks:</strong> tasks assigned to you (pending, in progress, blocked, recently completed) and, separately, tasks you <strong>supervise</strong>.</li>
<li><strong>Procurement:</strong> your requisitions awaiting approval or rejected; requisitions and purchase orders pending approval; RFQs past their closing date; purchase orders overdue for delivery.</li>
<li><strong>Finance:</strong> bank and petty cash reconciliations in progress or pending, overdue supplier bills and customer invoices, fixed assets awaiting capitalisation.</li>
<li><strong>HRM:</strong> leave requests, interviews, payroll runs awaiting approval, contracts expiring within 30 days.</li>
<li><strong>Inventory:</strong> items at or below their reorder point and your stock issues.</li>
</ul>
<p>Cards only appear for modules you can use. Warnings are amber; problems that need action now are red.</p>
<h1>Leadership dashboards</h1>
<p>Managers may also see the <strong>CEO</strong>, <strong>CFO</strong> and <strong>COO Dashboards</strong> and the <strong>Control Center</strong>, which summarise financial health (cash, receivables, payables), the sales pipeline, active projects and late milestones, open work orders, low stock, open support tickets, and overdue invoices and bills.</p>
<blockquote><strong>Start every day here.</strong> Clear what's red, plan what's amber, and you'll rarely be chased for anything.</blockquote>
HTML,
                ],
                [
                    'title' => 'Finding anything: search and lists',
                    'content' => <<<'HTML'
<h1>Global search</h1>
<p>The search box at the top of every page searches across the ERP at once — customers, suppliers, sales quotes, sales orders, sales invoices, journal entries, supplier bills and payments, work orders, products, projects, tasks, purchase requisitions and BOMs. Type a name, a code or a number (for example a quote number or project code) and jump straight to the record.</p>
<h1>Working with lists</h1>
<p>Most screens open on a list (table) of records. Lists share the same tools:</p>
<ul>
<li><strong>Search</strong> within the list, and <strong>filters</strong> such as status, priority, assignee, date or company.</li>
<li><strong>Status badges</strong> (for example PENDING, IN PROGRESS, SUBMITTED, COMPLETED) so you can see where each record is in its process.</li>
<li><strong>Action buttons</strong> on each row to view, edit or move the record to its next step. You only see the actions your permissions allow.</li>
</ul>
<h1>Codes and numbers</h1>
<p>Many records get an automatic code — project codes, task codes, quote numbers, PO numbers. Quote them in emails, chats and on paperwork. "Please review task TSK-0142" is faster and safer than "the kitchen task".</p>
<blockquote><strong>Before you create, search.</strong> It takes five seconds and prevents duplicate customers, suppliers, products and tasks.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'You need a menu item you can\'t see. What should you do?', 'options' => [
                    'Ask your manager to request the access from the ERP administrator' => true,
                    'Use a colleague\'s login for that task' => false,
                    'Do the work in a spreadsheet instead' => false,
                    'Nothing — the feature doesn\'t exist' => false,
                ]],
                ['q' => 'A list looks empty but you know records exist. What should you check first?', 'options' => [
                    'The "Viewing data for" company switcher' => true,
                    'Your screen brightness' => false,
                    'The appearance theme' => false,
                    'Whether chat is open' => false,
                ]],
                ['q' => 'Where can you see the tasks you supervise and requisitions waiting for approval?', 'options' => [
                    'On your Dashboard\'s attention cards' => true,
                    'Only in the audit log' => false,
                    'In the chat window' => false,
                    'Nowhere — you must ask each person' => false,
                ]],
                ['q' => 'When should you tick "Remember Me" on the sign-in page?', 'options' => [
                    'Only on your own work device' => true,
                    'On any computer, to save time' => false,
                    'Always on shared site computers' => false,
                    'Never — it deletes your password' => false,
                ]],
                ['q' => 'Select ALL the things global search can find.', 'options' => [
                    'Projects and tasks' => true,
                    'Customers and suppliers' => true,
                    'Sales quotes and purchase requisitions' => true,
                    'Your colleagues\' passwords' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 3: Tasks — how work gets done',
            'lessons' => [
                [
                    'title' => 'What makes a good task',
                    'content' => <<<'HTML'
<p>A task is a single piece of work with an owner and a finish line. Well-run teams everywhere manage their work the same way: small, clear, owned, dated and checked.</p>
<h1>The marks of a good task</h1>
<ul>
<li><strong>A clear name that says what "done" looks like.</strong> "Install and level kitchen base units — Flat 4" beats "Kitchen".</li>
<li><strong>One accountable owner.</strong> You can assign several people, but someone must clearly own it.</li>
<li><strong>A supervisor who checks it.</strong> Work that matters is reviewed before it's called complete.</li>
<li><strong>Realistic dates.</strong> A start date and a due date, agreed with the people doing the work.</li>
<li><strong>The right size.</strong> If a task takes more than a few days, break it into sub-tasks. Big vague tasks hide problems until it's too late.</li>
<li><strong>A definition of done.</strong> Use a checklist for the standard checks — so "complete" means the same thing every time.</li>
<li><strong>The right priority.</strong> Low, Medium, High or Critical. If everything is critical, nothing is.</li>
</ul>
<h1>What a task can be about</h1>
<p>In Thekan ERP a task is always about something — its <strong>subject</strong>:</p>
<ul>
<li>a <strong>Project</strong> (and optionally a component of it);</li>
<li>a <strong>Work Order</strong> in the workshop;</li>
<li>a <strong>CRM Lead</strong>, <strong>Opportunity</strong> or <strong>Customer</strong>;</li>
<li>a <strong>Purchase Requisition</strong> or <strong>Purchase Order</strong>;</li>
<li>or nothing at all — a <strong>General</strong> task.</li>
</ul>
<p>Linking the task to its subject means everyone looking at that project, work order or lead sees the work happening on it.</p>
HTML,
                ],
                [
                    'title' => 'The task lifecycle: start, submit, confirm',
                    'content' => <<<'HTML'
<p>Every task moves through the same statuses. You'll find tasks under <strong>Tasks → All Tasks</strong> and, for project work, <strong>Projects &amp; Maintenance → Project Tasks</strong>.</p>
<h1>The statuses</h1>
<ol>
<li><strong>Pending</strong> — created and assigned, not started.</li>
<li><strong>In progress</strong> — you selected <strong>Start</strong>. Only pending or blocked tasks can be started.</li>
<li><strong>Submitted for review</strong> — you finished and selected <strong>Submit</strong>. Only in-progress tasks can be submitted.</li>
<li><strong>Completed</strong> — the reviewer <strong>confirmed</strong> it.</li>
</ol>
<p>Two other statuses: <strong>Blocked</strong> (something is stopping the work) and <strong>Cancelled</strong> (no longer needed).</p>
<h1>Review and sign-off</h1>
<ul>
<li>Tasks normally <strong>require confirmation</strong>. When you submit, the task waits for review; only the task's <strong>creator</strong> or a <strong>supervisor</strong> can confirm or reject it.</li>
<li><strong>Confirm</strong> completes the task and notifies the assignees and supervisors.</li>
<li><strong>Reject</strong> needs a reason and sends the task back to <em>In progress</em>. Read the reason, fix the work and submit again.</li>
<li>If a task doesn't require confirmation, submitting it completes it straight away.</li>
<li>Only the creator or a supervisor can <strong>cancel</strong> a task.</li>
</ul>
<h1>Finding your tasks</h1>
<p>Use the list filters: <strong>mine</strong> (assigned to me), <strong>supervising</strong>, <strong>overdue</strong>, status, priority and subject. Lists show in-progress and submitted work first.</p>
<blockquote><strong>Never mark work complete that isn't.</strong> Submitting tells your supervisor "this is ready to check". If it isn't, you'll be rejected — and you've wasted their time and yours.</blockquote>
HTML,
                ],
                [
                    'title' => 'Checklists, blockers and dependencies',
                    'content' => <<<'HTML'
<h1>Checklists — the definition of done</h1>
<p>A task can have one or more checklists:</p>
<ul>
<li><strong>Plain</strong> checklists are tick-boxes: "Units levelled", "Doors aligned", "Site cleaned".</li>
<li><strong>Graded</strong> checklists score each item and pass at a threshold percentage — useful for quality inspections.</li>
<li>A checklist can be marked <strong>required</strong>. You cannot submit a task until every required checklist has passed — the system will tell you which one isn't complete.</li>
</ul>
<h1>Blockers — say when you're stuck</h1>
<p>If something outside your control is stopping the work, raise a <strong>blocker</strong> on the task straight away. Give it a category — <strong>material</strong>, <strong>approval</strong>, <strong>access</strong>, <strong>weather</strong>, <strong>resource</strong> or <strong>other</strong> — a severity, and a clear reason.</p>
<ul>
<li>An open blocker marks the task as blocked, so supervisors and managers can see it and act.</li>
<li>When the problem is solved, <strong>resolve</strong> the blocker with a short note of what fixed it.</li>
</ul>
<h1>Dependencies — what must finish first</h1>
<p>A dependency says "this task can't be done until that one is completed" — for example, "Install worktops" depends on "Install base units". A task with an incomplete predecessor shows as blocked. The system stops a task depending on itself and refuses dependencies that would go round in a circle.</p>
<blockquote><strong>Raise blockers early.</strong> A blocker raised on Monday can be fixed by Tuesday. The same problem discovered at Friday's review costs the week.</blockquote>
HTML,
                ],
                [
                    'title' => 'Time logs, supervisors and task QR codes',
                    'content' => <<<'HTML'
<h1>Time logs</h1>
<p>Log the time you spend on a task: a start time, an end time (or minutes) and a short note. Time logs build the task's actual hours, so planners can compare estimate with actual and price the next job better. Log time as you go, not from memory at the end of the week.</p>
<h1>Assignees and supervisors</h1>
<ul>
<li><strong>Assignees</strong> do the work. They're notified when they're assigned.</li>
<li><strong>Supervisors</strong> oversee and review it. They're notified too, and see the task in their "supervising" view and dashboard card.</li>
<li>External <strong>contractors</strong> can also be attached to a task, so their work is tracked alongside your team's.</li>
</ul>
<h1>Task QR codes</h1>
<p>Every task has a QR code. Print it and attach it to a job folder, a workshop item or a site area; scanning it opens the task directly, so anyone with access can see the latest status and instructions.</p>
<h1>Your daily task routine</h1>
<ol>
<li>Check your dashboard and your <strong>mine</strong> list.</li>
<li><strong>Start</strong> the task you're working on.</li>
<li>Log time, tick checklist items and raise blockers as they happen.</li>
<li><strong>Submit</strong> when the work truly meets the checklist.</li>
<li>If you supervise: confirm or reject submitted tasks promptly — don't let finished work sit waiting.</li>
</ol>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Which task can be started?', 'options' => [
                    'A pending or blocked task' => true,
                    'A completed task' => false,
                    'A cancelled task' => false,
                    'A task submitted for review' => false,
                ]],
                ['q' => 'You try to submit a task and the system says a checklist is not complete. Why?', 'options' => [
                    'Every required checklist must pass before a task can be submitted' => true,
                    'Checklists are optional and the message is an error' => false,
                    'Only supervisors can submit tasks' => false,
                    'The task has no due date' => false,
                ]],
                ['q' => 'Who can confirm or reject a task submitted for review?', 'options' => [
                    'The task\'s creator or a supervisor' => true,
                    'Any assignee on the task' => false,
                    'Anyone who can see the task' => false,
                    'Only the ERP administrator' => false,
                ]],
                ['q' => 'Your supervisor rejects your submitted task. What happens?', 'options' => [
                    'It goes back to In progress with the reason, for you to fix and resubmit' => true,
                    'It is deleted' => false,
                    'It is completed anyway' => false,
                    'It is cancelled permanently' => false,
                ]],
                ['q' => 'Select ALL the valid blocker categories.', 'options' => [
                    'Material' => true,
                    'Approval' => true,
                    'Weather' => true,
                    'Lunch break' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 4: Notifications, chat and approvals',
            'lessons' => [
                [
                    'title' => 'Notifications: the bell and your email',
                    'content' => <<<'HTML'
<p>Thekan ERP tells you when something needs you, so you don't have to keep checking.</p>
<h1>How you're notified</h1>
<ul>
<li><strong>The bell</strong> at the top of the screen shows your unread notifications. Click it for the latest, or <strong>View All Notifications</strong> for the full list.</li>
<li><strong>Email:</strong> important events — being assigned a task, a task being completed, approvals and other significant changes — also send a branded email with a link straight to the record.</li>
</ul>
<h1>Managing notifications</h1>
<p>On the Notifications page you can open a notification, mark it read or unread, mark all as read, and delete old ones.</p>
<h1>Good habits</h1>
<ul>
<li>Treat a notification as a request for action, not just information. Open it, act, then mark it read.</li>
<li>Use "unread" as your to-do list: if you can't deal with it now, leave it unread.</li>
<li>Don't create emails or WhatsApp messages to tell people what the ERP has already told them — trust the system, and follow up only if nothing happens.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Chat — and when to use it',
                    'content' => <<<'HTML'
<p><strong>Chat</strong> is Thekan's own organisation-wide messaging, inside the ERP. It updates automatically every few seconds — no third-party app needed.</p>
<h1>Using chat</h1>
<ul>
<li><strong>New Message</strong> starts a direct conversation with a colleague.</li>
<li><strong>New Group</strong> creates a named group with chosen participants — for example a project team.</li>
<li>You can edit or delete your own messages, see when someone is typing, and see when your message has been read.</li>
</ul>
<h1>Chat, task or record — which one?</h1>
<p>Chat is for quick conversation. It is <strong>not</strong> the place for anything the business needs to keep or act on.</p>
<ul>
<li><strong>Use chat</strong> for quick questions and coordination: "Are you on site at 9?"</li>
<li><strong>Use a task</strong> for work someone must do, with an owner and a due date.</li>
<li><strong>Use a blocker</strong> on the task when work is stuck.</li>
<li><strong>Use the record itself</strong> (notes, descriptions, rejection reasons, documents) for decisions and evidence — so they stay with the project, order or task for anyone who looks later.</li>
</ul>
<blockquote><strong>Test:</strong> if someone would need to find this in three months, it doesn't belong only in a chat. Put it on the record.</blockquote>
<p>Chat is internal. Keep it professional — messages are part of company records — and never send passwords, bank details or confidential client information over chat.</p>
HTML,
                ],
                [
                    'title' => 'How approvals work across the ERP',
                    'content' => <<<'HTML'
<p>Many records in Thekan ERP move through a controlled sequence of statuses before they count. The names vary by module, but the pattern is the same everywhere:</p>
<ol>
<li><strong>Draft</strong> — being prepared; can be edited; doesn't count yet.</li>
<li><strong>Submitted</strong> — sent for review.</li>
<li><strong>Approved</strong> or <strong>Rejected</strong> — decided by someone with approval permission. A rejection comes back with a reason.</li>
<li><strong>Posted</strong> — final: it now affects stock, costs or the accounts.</li>
<li><strong>Voided / Cancelled / Reversed</strong> — undone in a controlled, recorded way, never by deleting.</li>
</ol>
<p>Examples: purchase requisitions and orders wait for approval; timesheets are submitted then approved or rejected; project budgets are approved before they become the baseline; invoices are posted only after review.</p>
<h1>Segregation of duties</h1>
<p>A core control in every well-run company: <strong>the person who asks is not the person who approves</strong>, and the person who approves a payment is not the person who receives the goods. This protects the company — and protects you from suspicion if something goes wrong.</p>
<h1>As a requester</h1>
<ul>
<li>Submit complete records — missing details are the main reason for rejection.</li>
<li>If rejected, read the reason, fix it and resubmit. Don't create a duplicate.</li>
</ul>
<h1>As an approver</h1>
<ul>
<li>Approve promptly. A request waiting days for approval delays materials, site work and payments.</li>
<li>Check before you approve: right project, right quantities, within budget, supported by documents.</li>
<li>When you reject, give a clear, specific reason.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Where should a decision that the business needs to keep be recorded?', 'options' => [
                    'On the record itself (notes, documents, task), not only in chat' => true,
                    'Only in a chat message' => false,
                    'In a personal notebook' => false,
                    'Nowhere — decisions are verbal' => false,
                ]],
                ['q' => 'What does segregation of duties mean?', 'options' => [
                    'The person who requests something is not the person who approves it' => true,
                    'Each department uses a different ERP' => false,
                    'Managers do all the data entry' => false,
                    'Approvals are optional for urgent work' => false,
                ]],
                ['q' => 'Your requisition is rejected. What should you do?', 'options' => [
                    'Read the reason, correct it and resubmit' => true,
                    'Create a duplicate requisition and submit that' => false,
                    'Ask the supplier to deliver anyway' => false,
                    'Ignore it' => false,
                ]],
                ['q' => 'Which status usually means a record now affects stock, costs or the accounts?', 'options' => [
                    'Posted' => true,
                    'Draft' => false,
                    'Submitted' => false,
                    'Rejected' => false,
                ]],
                ['q' => 'Select ALL the things you should NOT send over chat.', 'options' => [
                    'Passwords' => true,
                    'Bank details' => true,
                    'Confidential client information' => true,
                    '"Are you on site at 9?"' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 5: Documents and records',
            'lessons' => [
                [
                    'title' => 'Document control: global good practice',
                    'content' => <<<'HTML'
<p>Design and construction firms live and die by their documents: drawings, specifications, BoQs, contracts, variations, inspection records, certificates and handover packs. International standards such as <strong>ISO 9001</strong> (quality management) and <strong>ISO 19650</strong> (information management for construction) agree on the basics of <strong>document control</strong>:</p>
<ul>
<li><strong>One controlled copy.</strong> Everyone works from the same current version, stored in one place.</li>
<li><strong>Clear status.</strong> Everyone can see whether a document is a draft, approved for use, or superseded.</li>
<li><strong>Version history.</strong> Old versions are kept, never overwritten, so you can always see what changed and when.</li>
<li><strong>Consistent naming.</strong> A predictable title or number makes documents findable.</li>
<li><strong>Access control.</strong> Confidential documents are seen only by people who need them.</li>
<li><strong>Traceability.</strong> Every document is linked to the project, task or record it belongs to.</li>
</ul>
<blockquote>Building or manufacturing from a superseded drawing is one of the most expensive mistakes a firm like ours can make. Good document control prevents it.</blockquote>
HTML,
                ],
                [
                    'title' => 'Documents in Thekan ERP',
                    'content' => <<<'HTML'
<p>Documents live in <strong>Document Management</strong>, and also appear on the records they're linked to — for example the <strong>Documents</strong> tab of a project, or a task.</p>
<h1>Uploading a document</h1>
<ul>
<li><strong>Title</strong> — clear and consistent, for example the drawing number and name.</li>
<li><strong>Category</strong> and <strong>Type</strong> — set up by the administrator (for example Drawings → Shop Drawing). Each type can restrict file extensions and size, and some types require an expiry date.</li>
<li><strong>Status</strong> — <strong>Draft</strong>, <strong>Active</strong>, <strong>Archived</strong> or <strong>Obsolete</strong>.</li>
<li><strong>Confidentiality</strong> — <strong>Public</strong>, <strong>Internal</strong>, <strong>Restricted</strong> or <strong>Confidential</strong>.</li>
<li><strong>Effective date</strong> and <strong>expiry date</strong> — important for certificates, insurances, permits and warranties.</li>
<li>The <strong>file</strong> itself — up to 20 MB.</li>
</ul>
<h1>New versions, not new documents</h1>
<p>When a drawing is revised, upload a <strong>new version</strong> of the existing document rather than a separate document. History is kept and everyone sees the latest. Mark replaced documents <strong>Obsolete</strong> so nobody works from them.</p>
<h1>Linking documents</h1>
<p>Link a document to the records it belongs to — a project, a task and more — with a relation type and remarks. One document can be linked to several records, so it's stored once and found everywhere it matters.</p>
<h1>The document audit trail</h1>
<p>Document Management records what happens to each document, so it's clear who uploaded, changed or used it.</p>
<blockquote><strong>Confidentiality:</strong> client drawings, contracts, prices and personal data are never Public. When unsure, choose the higher level.</blockquote>
HTML,
                ],
                [
                    'title' => 'Master data: customers, suppliers, contractors and locations',
                    'content' => <<<'HTML'
<p><strong>Master data</strong> is the shared reference information every transaction uses: customers, suppliers, contractors, products, users, companies, departments, vehicles, drivers and locations. Global practice is to keep master data <strong>few, clean and owned</strong> — a small number of trained people create and maintain it, and everyone else uses it.</p>
<h1>At Thekan</h1>
<ul>
<li><strong>Customers</strong> (with contacts, addresses and sites) are used by CRM, Sales, Projects and Finance.</li>
<li><strong>Suppliers</strong> (with addresses and contacts) by Procurement, Inventory and Finance.</li>
<li><strong>Contractors</strong> (subcontractors) are assigned to project tasks and project costs.</li>
<li><strong>Locations</strong> describe places down to <strong>blocks, floors and rooms</strong>, and stores and shelves for stock. Customer sites can be linked to locations, so a project, its tasks and its installed items can be traced to the exact room.</li>
</ul>
<h1>Your part</h1>
<ul>
<li>Search before you create — duplicates are the most common master-data problem.</li>
<li>If details are wrong (a phone number, an address, a bank account), report it to the data owner rather than creating a new record.</li>
<li>Bank details for suppliers and contractors are changed only through the proper verification process — never on the strength of an email or a phone call.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'A shop drawing has been revised. What should you do in Document Management?', 'options' => [
                    'Upload a new version of the existing document and mark the replaced one Obsolete if needed' => true,
                    'Upload it as a completely separate document' => false,
                    'Delete the old drawing' => false,
                    'Send it by chat only' => false,
                ]],
                ['q' => 'Which confidentiality level suits a client contract?', 'options' => [
                    'Confidential' => true,
                    'Public' => false,
                    'It doesn\'t need one' => false,
                    'Internal, posted on the noticeboard' => false,
                ]],
                ['q' => 'Select ALL the document statuses in Thekan ERP.', 'options' => [
                    'Draft' => true,
                    'Active' => true,
                    'Obsolete' => true,
                    'Forgotten' => false,
                ]],
                ['q' => 'Why link a document to records instead of uploading copies everywhere?', 'options' => [
                    'It is stored once and found on every record it belongs to' => true,
                    'Linking deletes the original file' => false,
                    'Copies are faster to update' => false,
                    'Linked documents are always public' => false,
                ]],
                ['q' => 'A supplier emails asking you to change their bank account details. What should you do?', 'options' => [
                    'Pass it to the proper verification process — never change it on an email alone' => true,
                    'Update the supplier record straight away' => false,
                    'Create a new supplier with the new bank details' => false,
                    'Reply with the old bank details to confirm' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 6: Security, control and getting help',
            'lessons' => [
                [
                    'title' => 'Permissions, roles and your account',
                    'content' => <<<'HTML'
<h1>Least privilege</h1>
<p>Global good practice is <strong>least privilege</strong>: everyone gets the access their job needs, and no more. In Thekan ERP, <strong>roles</strong> bundle <strong>permissions</strong> (for example "view project costs", "approve timesheets", "post invoices"). What you can see in the menu and the buttons you get on each record come from your roles.</p>
<ul>
<li>If you need more access, your manager requests it from the ERP administrator.</li>
<li>When you change roles or leave a project, access that's no longer needed should be removed.</li>
</ul>
<h1>Protecting your account</h1>
<ul>
<li><strong>Never share your password</strong>, and never use someone else's login — not even to "help". Every action is recorded against the account that did it.</li>
<li>Use a long, unique password you don't use anywhere else.</li>
<li><strong>Lock your screen</strong> when you step away (Windows + L) and log out on shared computers.</li>
<li>Be suspicious of emails asking you to "log in to the ERP" through a link — check the address, and when in doubt, go to the ERP yourself.</li>
<li>Report anything odd — an action you didn't take, a password reset you didn't request — straight away.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'The audit trail',
                    'content' => <<<'HTML'
<p>Every important action in Thekan ERP — creating, editing, approving, rejecting, posting, cancelling, reversing — is written to the <strong>audit log</strong>: who did it, what they did, to which record, and when.</p>
<h1>Why it matters</h1>
<ul>
<li><strong>Accountability:</strong> it's always clear who did what. That protects honest people.</li>
<li><strong>Investigation:</strong> when figures look wrong, the history shows what happened.</li>
<li><strong>Trust:</strong> auditors, banks, government clients and partners rely on records that can't be changed silently.</li>
</ul>
<p>People whose role allows it can open the audit log from <strong>Activity Log</strong> in the user menu, or from <strong>Audit Logs</strong> and <strong>Audit Analytics</strong> under Master Data.</p>
<blockquote>The audit trail is one more reason never to share logins: if someone uses your account, their actions are recorded as yours.</blockquote>
HTML,
                ],
                [
                    'title' => 'Getting help and your next courses',
                    'content' => <<<'HTML'
<h1>When you're stuck</h1>
<ol>
<li><strong>Read the module guide</strong> in the menu — they explain each process step by step.</li>
<li><strong>Ask your supervisor</strong> or a colleague experienced in that module.</li>
<li><strong>Use Help &amp; Support</strong> in the user menu to report a problem with the system. Include what you were doing, the record code or number, and the exact message you saw. A screenshot helps.</li>
</ol>
<h1>Your next courses</h1>
<p>This course is the foundation. Each module course teaches global best practice for that area, the process at Thekan, and the ERP feature by feature. Your manager will tell you which apply to your role; for example:</p>
<ul>
<li><strong>Project &amp; site delivery</strong> (project managers, site and installation supervisors, engineers, QSs, architects): <em>Projects, Site Installation &amp; Construction in Thekan ERP</em>.</li>
<li>Further courses for Sales &amp; Estimating, CRM, Procurement, Inventory &amp; Stores, Furniture Production, Finance, Fixed Assets, HR and Document Control follow.</li>
</ul>
<p>Mark this lesson complete, then take the final assessment. You need 80% to pass, and you have up to 3 attempts.</p>
HTML,
                ],
            ],
            'final' => [
                'title' => 'Thekan ERP Foundations — Final Assessment',
                'slug' => 'erp-foundations-assessment',
                'description' => '<p>The final assessment for Thekan ERP Foundations. It covers every module, with an emphasis on real situations at Thekan.</p>',
                'instructions' => <<<'HTML'
<p>This assessment has <strong>20 questions</strong> and you have <strong>30 minutes</strong>.</p>
<ul>
<li>You need <strong>80%</strong> to pass. You have up to <strong>3 attempts</strong>.</li>
<li>Some questions ask you to <strong>select all that apply</strong> — choose every correct option, and only those, to get the mark.</li>
<li>Questions appear in random order. You can move between them and flag ones to come back to.</li>
<li>This assessment is monitored: leaving the exam window or copying text is recorded. Work on your own.</li>
</ul>
HTML,
                'duration_minutes' => 30,
                'pass_percentage' => 80,
                'max_attempts' => 3,
                'questions' => [
                    ['q' => 'A storekeeper receives 40 sheets of 18mm MDF on Monday but plans to enter the goods receipt on Friday. What is the main problem?', 'options' => [
                        'Stock, costs and planning are wrong all week, and someone may reorder — record it when it arrives' => true,
                        'There is no problem as long as it is entered the same month' => false,
                        'Only the supplier is affected' => false,
                        'Goods receipts are optional' => false,
                    ]],
                    ['q' => 'You need to add a new supplier. What should you do first?', 'options' => [
                        'Search to make sure the supplier doesn\'t already exist' => true,
                        'Create it with a slightly different name to be safe' => false,
                        'Ask the supplier to create it' => false,
                        'Record purchases in a spreadsheet instead' => false,
                    ]],
                    ['q' => 'Which module holds Bills of Quantities and Sales Quotes?', 'options' => [
                        'Sales' => true,
                        'HRM' => false,
                        'Inventory' => false,
                        'Document Management' => false,
                    ]],
                    ['q' => 'Which module runs the workshop with BOMs, routings and work orders?', 'options' => [
                        'Production' => true,
                        'CRM' => false,
                        'Finance' => false,
                        'Chat' => false,
                    ]],
                    ['q' => 'You are about to raise an important purchase order. What should you check at the top of the screen?', 'options' => [
                        'That "Viewing data for" shows the right company' => true,
                        'The notification bell count' => false,
                        'The appearance theme' => false,
                        'That chat is closed' => false,
                    ]],
                    ['q' => 'Select ALL the statuses a task can have.', 'options' => [
                        'Pending' => true,
                        'Submitted for review' => true,
                        'Blocked' => true,
                        'Posted' => false,
                    ]],
                    ['q' => 'A task requires confirmation and you select Submit. What happens?', 'options' => [
                        'It becomes Submitted for review and waits for the creator or a supervisor' => true,
                        'It is completed immediately' => false,
                        'It is cancelled' => false,
                        'It is sent to the client' => false,
                    ]],
                    ['q' => 'The worktops can\'t be fitted until the base units are installed. How should this be recorded?', 'options' => [
                        'Add a dependency: "Install worktops" depends on "Install base units"' => true,
                        'Write it in the task name' => false,
                        'Tell the fitter by chat only' => false,
                        'Cancel the worktop task' => false,
                    ]],
                    ['q' => 'Site access is refused because the client\'s security hasn\'t cleared your team. What should you do on the task?', 'options' => [
                        'Raise a blocker with category Access, a severity and a clear reason' => true,
                        'Mark the task completed' => false,
                        'Delete the task' => false,
                        'Do nothing until someone asks' => false,
                    ]],
                    ['q' => 'What is a graded checklist?', 'options' => [
                        'A checklist that scores items and passes at a threshold percentage' => true,
                        'A checklist only managers can see' => false,
                        'A checklist without items' => false,
                        'A list of school grades' => false,
                    ]],
                    ['q' => 'What does scanning a task\'s QR code do?', 'options' => [
                        'Opens the task directly, showing its latest status and instructions' => true,
                        'Completes the task' => false,
                        'Prints an invoice' => false,
                        'Sends the task to the client' => false,
                    ]],
                    ['q' => 'Your colleague asks you to "just post a quick update" on a client decision in a group chat. What is best practice?', 'options' => [
                        'Record the decision on the project or task record; use chat only to point people to it' => true,
                        'Chat is enough — everyone reads it' => false,
                        'Don\'t record client decisions' => false,
                        'Email it to yourself' => false,
                    ]],
                    ['q' => 'Select ALL the events that send a notification and email.', 'options' => [
                        'You are assigned a task' => true,
                        'A task you work on is completed' => true,
                        'A significant approval or posting that concerns you' => true,
                        'Every time you open a list' => false,
                    ]],
                    ['q' => 'You approve purchase orders. What should you check before approving?', 'options' => [
                        'Right project, right quantities, within budget, and supported by documents' => true,
                        'Nothing — approval is a formality' => false,
                        'Only that the supplier name is spelt correctly' => false,
                        'That it was submitted in the morning' => false,
                    ]],
                    ['q' => 'Which document status should a superseded drawing have?', 'options' => [
                        'Obsolete' => true,
                        'Active' => false,
                        'Draft' => false,
                        'Public' => false,
                    ]],
                    ['q' => 'Select ALL the good practices for documents in Thekan ERP.', 'options' => [
                        'Upload revisions as new versions of the same document' => true,
                        'Link the document to the project or task it belongs to' => true,
                        'Set an expiry date on certificates and permits' => true,
                        'Mark client contracts as Public' => false,
                    ]],
                    ['q' => 'Why can a project, its tasks and installed items be traced to an exact room?', 'options' => [
                        'Locations are recorded down to blocks, floors and rooms, and customer sites link to them' => true,
                        'Every room has its own ERP' => false,
                        'Rooms are recorded in chat' => false,
                        'They can\'t' => false,
                    ]],
                    ['q' => 'A colleague on leave asks you to use their login to approve a timesheet. What do you do?', 'options' => [
                        'Decline; ask your manager to arrange approval through proper access' => true,
                        'Use it — it\'s for work' => false,
                        'Use it and change their password afterwards' => false,
                        'Share it with the team so anyone can help' => false,
                    ]],
                    ['q' => 'What does the audit log record?', 'options' => [
                        'Who did what, to which record, and when' => true,
                        'Only failed sign-ins' => false,
                        'Only chat messages' => false,
                        'Nothing — it is optional' => false,
                    ]],
                    ['q' => 'You hit a system error. What should you include when you report it through Help & Support?', 'options' => [
                        'What you were doing, the record code or number, the exact message, and a screenshot' => true,
                        'Only "it doesn\'t work"' => false,
                        'Your password, so they can test it' => false,
                        'Nothing — just retry until it works' => false,
                    ]],
                ],
            ],
        ],
    ],

    // Recommended to everyone: it's the first course in every ERP learning path.
    'recommended_for' => [
        'Architect', 'Interior Designer', 'Project Manager', 'Site Supervisor', 'Site Engineer',
        'Installation Supervisor', 'Quantity Surveyor', 'Estimator', 'Sales Executive',
        'Procurement Officer', 'Storekeeper', 'Production Supervisor', 'Accountant', 'HR Officer',
        'ERP Administrator',
    ],
];
