<?php

/*
 * "Architect Induction: Working at Thekan" — content for ThekanServicesSeeder.
 *
 * Seven modules. Each ends with a module test (built into the course
 * outline); the last module's test is the comprehensive final assessment.
 * The ERP lessons describe Thekan ERP as built: the "Projects & Assets"
 * menu, its task/budget/cost/timesheet/document statuses and workflows.
 *
 * Quiz format: 'q' => question, 'options' => [text => is_correct, ...].
 * More than one correct option makes it a "select all that apply" question.
 */

return [
    // Module tests are named "Module N Test: …" with slugs induction-module-N-test.
    'test_prefix' => 'induction',
    'test_label' => '',

    'course' => [
        'title' => 'Architect Induction: Working at Thekan',
        'slug' => 'architect-induction',
        'description' => <<<'HTML'
<p>Welcome to Thekan Services Limited. This induction prepares you to deliver our projects from first design to final handover — to the standard our clients expect.</p>
<p>We work for government and for very high-end private clients. That means discretion, precision and no surprises: every decision recorded, every drawing controlled, every naira accounted for. This course shows you how we work and how to run your part of a project in Thekan ERP, where we manage tasks, costs and documents.</p>
<p>Lessons open in order. Each module ends with a short test, and the final module ends with the induction assessment. Pass them all to receive your certificate.</p>
HTML,
        'catalog' => [
            'subtitle' => 'How we deliver projects from design to finish — and how to run your work in Thekan ERP.',
            'category' => 'Induction',
            'level' => 'all_levels',
            'duration_minutes' => 210,
            'instructor_name' => 'Thekan Services Limited',
            'instructor_bio' => 'Prepared by the Thekan project delivery team for architects joining our studio.',
            'outcomes' => [
                'Explain how a Thekan project runs from brief to handover, and your part in it',
                'Handle government and high-profile clients with the right protocol and discretion',
                'Apply our design and site quality checks, and avoid rework',
                'Run tasks in Thekan ERP — from start through review to supervisor sign-off',
                'Record costs against the right budget line and read budget against actual',
                'Control drawings and documents: versions, status and confidentiality',
            ],
        ],
    ],

    'modules' => [

        // ------------------------------------------------------------------
        [
            'title' => 'Module 1: Welcome to Thekan',
            'lessons' => [
                [
                    'title' => 'Who we are and how we work',
                    'preview' => true,
                    'content' => <<<'HTML'
<p>Thekan Services Limited designs and delivers buildings for clients who cannot afford mistakes: state government, public institutions and private clients at the very top end of the market. They choose us because we are <strong>efficient</strong> and our quality is <strong>never in question</strong>.</p>
<h1>What we stand for</h1>
<ul>
  <li><strong>Quality without excuses.</strong> Every drawing checked, every detail resolved before it reaches site, every finish inspected before the client sees it.</li>
  <li><strong>Efficiency.</strong> We plan the work, do it once, and keep it moving. Rework is the most expensive thing we do.</li>
  <li><strong>Discretion.</strong> Our clients' projects, plans and costs are confidential. We do not discuss them outside the team.</li>
  <li><strong>Accountability.</strong> If it isn't recorded, it didn't happen. Decisions, approvals, costs and drawing issues all live in Thekan ERP.</li>
</ul>
<h1>How this induction works</h1>
<p>There are seven modules. The first three are about how we work; the next three walk you through Thekan ERP, our system for tasks, costs and documents; the last brings it all together in a worked scenario and the induction assessment.</p>
<p>Take your time. Where something differs from how you've worked before, ask your project manager — that conversation is part of your induction too.</p>
HTML,
                ],
                [
                    'title' => 'Our project lifecycle: design to finish',
                    'content' => <<<'HTML'
<p>Every Thekan project follows the same path. Knowing where a project is tells you what is expected of you and what must be signed off before it moves on.</p>
<ol>
  <li><strong>Brief and appointment.</strong> We confirm the client's requirements, budget, programme and approvals route. Nothing is drawn until the brief is agreed in writing.</li>
  <li><strong>Concept design.</strong> Options that answer the brief, with a first cost check. The client chooses a direction.</li>
  <li><strong>Schematic and design development.</strong> The chosen scheme is developed and coordinated with structure and services. Cost is checked again against the budget.</li>
  <li><strong>Statutory approvals.</strong> Planning and building permits, and for government work, the approvals the ministry or agency requires.</li>
  <li><strong>Construction documentation.</strong> Drawings, specifications and schedules complete enough to price and build without guesswork.</li>
  <li><strong>Procurement.</strong> Contractors and suppliers are appointed; materials are ordered against approved specifications.</li>
  <li><strong>Construction and supervision.</strong> We inspect, answer site queries, issue instructions and keep quality and programme on track.</li>
  <li><strong>Completion and handover.</strong> Snagging, final inspection, as-built drawings, manuals and a clean handover to the client.</li>
</ol>
<p>Each stage ends with a <strong>sign-off</strong>. In Thekan ERP the stages appear as the project's <em>components</em> and <em>milestones</em>, so everyone can see where the project stands.</p>
HTML,
                ],
                [
                    'title' => 'Your role and the people you work with',
                    'content' => <<<'HTML'
<p>As an architect at Thekan you own the design quality of the work you are given — and you are accountable for it being delivered on time and within its budget.</p>
<h1>Who's who on a project</h1>
<ul>
  <li><strong>Project manager</strong> — owns the project in Thekan ERP: programme, budget, client relationship and approvals. Your first point of contact.</li>
  <li><strong>Supervisor</strong> — the person who reviews and confirms your tasks. A task that needs confirmation is not finished until they sign it off.</li>
  <li><strong>Site supervisor</strong> — runs day-to-day work on site and reports progress and problems.</li>
  <li><strong>Quantity surveyor and finance</strong> — budgets, costs, valuations and payments.</li>
  <li><strong>Consultants and contractors</strong> — structural and services engineers, contractors and suppliers. Some tasks in the ERP are linked to the contractor doing the work.</li>
</ul>
<h1>Your first 30 days</h1>
<ul>
  <li>Get your Thekan ERP login and check you can see your projects under <em>Projects &amp; Assets</em>.</li>
  <li>Read the current drawings and the brief for each project you join.</li>
  <li>Walk each active site with the site supervisor.</li>
  <li>Complete this induction and its assessment.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What must be in place before design work starts on a Thekan project?', 'options' => [
                    'A brief agreed in writing with the client' => true,
                    'A full set of construction drawings' => false,
                    'A contractor already appointed' => false,
                    'Verbal approval from the client is enough' => false,
                ]],
                ['q' => 'Which of these are Thekan values? (Select all that apply.)', 'options' => [
                    'Efficiency — plan the work and do it once' => true,
                    'Discretion about our clients\' projects' => true,
                    'Accountability — record decisions and approvals' => true,
                    'Speed over checking, to keep clients happy' => false,
                ]],
                ['q' => 'In Thekan ERP, how can you see which stage a project has reached?', 'options' => [
                    'From the project\'s components and milestones' => true,
                    'Only by asking the client' => false,
                    'From the email history' => false,
                    'It isn\'t recorded anywhere' => false,
                ]],
                ['q' => 'A task in the ERP needs confirmation. When is it finished?', 'options' => [
                    'When the supervisor confirms it' => true,
                    'As soon as you mark it complete' => false,
                    'When the due date passes' => false,
                    'When the client mentions it on a call' => false,
                ]],
                ['q' => 'Who is your first point of contact on a project?', 'options' => [
                    'The project manager' => true,
                    'The client directly' => false,
                    'The contractor' => false,
                    'Finance' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 2: Serving government and high-end clients',
            'lessons' => [
                [
                    'title' => 'Client protocol and communication',
                    'content' => <<<'HTML'
<p>Our clients include the office of the Governor and private clients who expect the highest standard of service. How we communicate is part of the quality we deliver.</p>
<h1>The rules</h1>
<ul>
  <li><strong>One voice.</strong> Formal communication with the client goes through the project manager, or as they direct. Don't make promises about cost, programme or scope on your own.</li>
  <li><strong>Prepared, always.</strong> Never arrive at a client meeting without the current drawings, the open issues and the decisions you need from them.</li>
  <li><strong>Respect protocol.</strong> Government meetings have hierarchy and formality. Use titles, arrive early, follow the lead of the most senior Thekan person present.</li>
  <li><strong>Minute everything.</strong> Every meeting ends with written minutes: decisions, actions, owners and dates — shared within 24 hours and filed in Thekan ERP against the project.</li>
  <li><strong>Calm and precise.</strong> If something has gone wrong, say what happened, what it affects and what we propose. No surprises — clients must hear bad news from us first.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Confidentiality and security',
                    'content' => <<<'HTML'
<p>Government buildings and private residences carry real security risk. Plans, layouts, security arrangements and costs must not leak.</p>
<ul>
  <li><strong>Need to know.</strong> Share drawings and documents only with people working on the project, and only what they need.</li>
  <li><strong>No public posting.</strong> Never post images, drawings or site photos on social media or portfolios without written approval from the project manager and the client.</li>
  <li><strong>Use the right confidentiality level.</strong> In Thekan ERP every document has one: <em>public</em>, <em>internal</em>, <em>restricted</em> or <em>confidential</em>. Client security layouts, government documents and cost reports are at least <em>restricted</em>; anything about security arrangements is <em>confidential</em>.</li>
  <li><strong>Keep files in the system.</strong> Don't keep project documents on personal phones, USB drives or personal email. Thekan ERP records who opened and changed each document.</li>
  <li><strong>Site access.</strong> Follow the site's security procedures, carry ID and never bring unauthorised visitors.</li>
</ul>
<p>If you think information has leaked, tell your project manager immediately. Quick reporting limits the damage.</p>
HTML,
                ],
                [
                    'title' => 'Approvals, sign-offs and change requests',
                    'content' => <<<'HTML'
<p>On high-value projects, most disputes come from changes that were never formally agreed. We protect our clients and ourselves by managing change properly.</p>
<h1>Approvals and sign-offs</h1>
<ul>
  <li>Each design stage ends with a written client sign-off, filed in Thekan ERP and linked to the project.</li>
  <li>Government projects often need approval from more than one office. Record who approved what, and when.</li>
</ul>
<h1>When the client asks for a change</h1>
<ol>
  <li><strong>Record it.</strong> Write down exactly what was asked, by whom and when.</li>
  <li><strong>Assess it.</strong> What does it change in the design, the cost and the programme? Work it out with the project manager and the quantity surveyor.</li>
  <li><strong>Get approval in writing</strong> for the change and its cost and time effect before any work proceeds.</li>
  <li><strong>Update the record.</strong> Revise the drawings as a new document version, and update the tasks and budget in Thekan ERP.</li>
</ol>
<p>A change made without this process is unpaid work and an argument waiting to happen — however senior the person who asked.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'A senior client asks you, in passing on site, to move a wall. What do you do first?', 'options' => [
                    'Record the request and raise it with the project manager to assess cost and time' => true,
                    'Instruct the contractor straight away — the client asked' => false,
                    'Ignore it unless they ask again' => false,
                    'Change the drawing quietly' => false,
                ]],
                ['q' => 'Which confidentiality level should a document describing a residence\'s security arrangements have?', 'options' => [
                    'Confidential' => true,
                    'Internal' => false,
                    'Public' => false,
                    'It doesn\'t need one' => false,
                ]],
                ['q' => 'When should meeting minutes be shared and filed?', 'options' => [
                    'Within 24 hours, filed in Thekan ERP against the project' => true,
                    'At the end of the project' => false,
                    'Only if the client asks' => false,
                    'They aren\'t needed for government meetings' => false,
                ]],
                ['q' => 'Which of these are allowed? (Select all that apply.)', 'options' => [
                    'Sharing drawings with a consultant working on the project' => true,
                    'Posting site photos on social media without approval' => false,
                    'Keeping project files on a personal USB drive' => false,
                    'Keeping documents in Thekan ERP' => true,
                ]],
                ['q' => 'Something has gone wrong that will delay the project. How should the client hear about it?', 'options' => [
                    'From us first — what happened, what it affects and what we propose' => true,
                    'Only once it\'s fixed' => false,
                    'From the contractor' => false,
                    'They don\'t need to know' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 3: Quality and efficiency',
            'lessons' => [
                [
                    'title' => 'Design quality checks',
                    'content' => <<<'HTML'
<p>Errors found on paper cost minutes. Errors found on site cost weeks and reputation. Every drawing set is checked before it leaves the studio.</p>
<h1>Before any drawing is issued</h1>
<ul>
  <li><strong>Self-check</strong> against the brief, the approved scheme and the latest consultant drawings.</li>
  <li><strong>Coordination</strong> — structure, services and architecture agree: no clashes, levels consistent, openings matched.</li>
  <li><strong>Standards</strong> — title block complete, correct scale, north point, revision noted and described.</li>
  <li><strong>Peer review</strong> by another architect for anything going to site or to the client.</li>
  <li><strong>Buildability</strong> — could a contractor build this from the drawing without phoning us?</li>
</ul>
<p>In Thekan ERP, design tasks often carry a <em>checklist</em>. Some are simple tick lists; others are <em>graded</em>, where each item has a weight and a score. Work through every item honestly — your supervisor sees the result before confirming the task.</p>
HTML,
                ],
                [
                    'title' => 'Site quality and snagging',
                    'content' => <<<'HTML'
<p>Our clients judge us by the finished building. Quality on site is managed, not hoped for.</p>
<ul>
  <li><strong>Inspect against the drawings and specification</strong>, not memory. Take the current revision to site.</li>
  <li><strong>Hold points.</strong> Some work must be inspected before it is covered up — reinforcement, waterproofing, services in walls. Agree these with the site supervisor in advance.</li>
  <li><strong>Record what you find.</strong> Photos, location and the instruction given. Problems that block progress are logged as blockers on the task in Thekan ERP.</li>
  <li><strong>Snagging.</strong> Before handover, every room is inspected and every defect listed with an owner and a date. We find the defects; the client should never have to.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Working efficiently and avoiding rework',
                    'content' => <<<'HTML'
<p>Efficiency is not rushing. It is doing the right work, once, in the right order.</p>
<ul>
  <li><strong>Plan your week</strong> from your tasks in Thekan ERP. Know what is due and what depends on you.</li>
  <li><strong>Respect dependencies.</strong> If a task can't start until another is finished, the ERP shows the link. Don't start work on assumptions that will change.</li>
  <li><strong>Raise blockers early.</strong> A missing approval or late information is cheap to fix today and expensive next week.</li>
  <li><strong>Hand over cleanly.</strong> When you submit work for review, make it complete, with notes on anything unresolved.</li>
  <li><strong>Log your time honestly.</strong> It shows where effort goes and keeps our fees and programmes realistic.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Site safety basics',
                    'content' => <<<'HTML'
<p>Nobody's work is worth an injury. On every site visit:</p>
<ul>
  <li>Report to the site office, sign in and attend the site induction.</li>
  <li>Wear the required protective equipment: helmet, safety boots, high-visibility vest, and more where the site requires it.</li>
  <li>Stay out of exclusion zones — lifting, excavations, working at height — unless you are escorted.</li>
  <li>Stop and report anything unsafe to the site supervisor. You may stop work you believe is dangerous.</li>
  <li>Never visit a live site alone without telling the office where you are.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Which checks happen before a drawing goes to site? (Select all that apply.)', 'options' => [
                    'Coordination with structural and services drawings' => true,
                    'Peer review by another architect' => true,
                    'Revision noted in the title block' => true,
                    'Only a check of the font size' => false,
                ]],
                ['q' => 'What is a "hold point" on site?', 'options' => [
                    'Work that must be inspected before it is covered up' => true,
                    'A rest break for workers' => false,
                    'A place to store materials' => false,
                    'The end of the project' => false,
                ]],
                ['q' => 'A graded checklist on a task in Thekan ERP…', 'options' => [
                    'gives each item a weight and a score, reviewed by your supervisor' => true,
                    'is optional and nobody reads it' => false,
                    'can only be ticked by the client' => false,
                    'replaces the drawing review' => false,
                ]],
                ['q' => 'Your task depends on structural drawings that are late. What is the efficient thing to do?', 'options' => [
                    'Report it as a blocker straight away so it can be chased' => true,
                    'Start anyway and guess the structure' => false,
                    'Wait quietly until they arrive' => false,
                    'Mark your task complete' => false,
                ]],
                ['q' => 'You see something unsafe on site. What should you do?', 'options' => [
                    'Stop and report it to the site supervisor' => true,
                    'Ignore it — safety is the contractor\'s job' => false,
                    'Take a photo for later' => false,
                    'Mention it at the next monthly meeting' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 4: Thekan ERP — projects and tasks',
            'lessons' => [
                [
                    'title' => 'Finding your way in Thekan ERP',
                    'content' => <<<'HTML'
<p>Thekan ERP is where we run our projects. You'll use it every day. Your login is set up by the office; if you can't see a project you work on, ask your project manager.</p>
<h1>The Projects &amp; Assets menu</h1>
<ul>
  <li><strong>Projects</strong> — every project with its code, client, project manager, status (<em>draft, planned, active, on hold, completed</em> or <em>cancelled</em>), priority and dates.</li>
  <li><strong>Project Tasks</strong> — the work itself. This is where you'll spend most of your time.</li>
  <li><strong>Project Milestones</strong> — the key dates and stage sign-offs.</li>
  <li><strong>Project Costs</strong> and <strong>Project Budgets</strong> — what we planned to spend and what we have spent (Module 5).</li>
  <li><strong>Project Timesheets</strong> — your time on each project.</li>
  <li><strong>Task Analytics</strong> and <strong>Project Profitability</strong> — how projects are performing.</li>
  <li><strong>Document Management</strong> — drawings and documents (Module 6).</li>
</ul>
<p>Open a project to see its <strong>components</strong> (the parts of the work, such as stages or buildings), its milestones, tasks, costs and documents in one place. The <strong>Gantt</strong> view shows tasks against time, with the links between them.</p>
HTML,
                ],
                [
                    'title' => 'Working a task from start to sign-off',
                    'content' => <<<'HTML'
<p>Every task has a code, a name, a supervisor, a priority (<em>low, medium, high</em> or <em>critical</em>), start and due dates, and estimated hours and cost. People on a task are either the <strong>assignee</strong> (doing the work) or a <strong>reviewer</strong>.</p>
<h1>The task statuses</h1>
<ol>
  <li><strong>Pending</strong> — planned, not started.</li>
  <li><strong>In progress</strong> — you are working on it. Keep the progress percentage honest.</li>
  <li><strong>Blocked</strong> — something outside your control is stopping it (see the next lesson).</li>
  <li><strong>Submitted for review</strong> — you've finished and it's waiting for your supervisor.</li>
  <li><strong>Completed</strong> — done and, where required, confirmed.</li>
  <li><strong>Cancelled</strong> — no longer needed.</li>
</ol>
<h1>Confirmation</h1>
<p>Many design tasks are marked <em>confirmation required</em>. When you submit them, your supervisor either <strong>confirms</strong> the task or <strong>rejects</strong> it with a reason. A rejection isn't a failure — read the reason, fix it, and resubmit. Every confirmation and rejection is recorded on the task's history.</p>
<p><strong>Good practice:</strong> before you submit, complete the checklist, attach or link the documents, and add a note on anything unresolved.</p>
HTML,
                ],
                [
                    'title' => 'Blockers, dependencies and checklists',
                    'content' => <<<'HTML'
<h1>Blockers</h1>
<p>When something stops your work, report a <strong>blocker</strong> on the task straight away. Choose the category — <em>material, approval, access, weather, resource</em> or <em>other</em> — set how severe it is, and describe it clearly. The task shows as blocked, so the project manager can act. When the problem is fixed, the blocker is resolved with a note on how.</p>
<h1>Dependencies</h1>
<p>Some tasks can't start until others finish — you can't detail a façade before the structural grid is fixed. These links are set up as task dependencies and shown on the Gantt. Check them before you start, and tell your project manager if one is wrong.</p>
<h1>Checklists</h1>
<p>Tasks can carry checklists: <em>plain</em> (tick each item) or <em>graded</em> (each item has a weight and a maximum score, and the reviewer records the score achieved). Use them as your quality gate before submitting.</p>
HTML,
                ],
                [
                    'title' => 'Logging your time',
                    'content' => <<<'HTML'
<p>Time records show where effort goes, keep fees realistic and are needed to bill some clients.</p>
<h1>On the task</h1>
<p>Log time against the task you're working on — start a <strong>timer</strong> while you work, or add the time <strong>manually</strong> afterwards. The task's actual hours build up from these logs.</p>
<h1>Project Timesheets</h1>
<p>Your time also appears on <strong>Project Timesheets</strong>, by date, project and task, with a description of the work. A timesheet goes from <em>draft</em> to <em>submitted</em>, then is <em>approved</em> or <em>rejected</em> (with a reason) by your manager. Submit yours every week — late timesheets hold up costs and billing.</p>
<p>Be precise and honest. Time logged against the wrong project distorts its cost and our profitability reports.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'You\'ve finished a design task that needs confirmation. What status do you move it to?', 'options' => [
                    'Submitted for review' => true,
                    'Completed' => false,
                    'Cancelled' => false,
                    'Pending' => false,
                ]],
                ['q' => 'Your supervisor rejects your task. What happens next?', 'options' => [
                    'Read the reason, fix the work and resubmit' => true,
                    'Mark it completed anyway' => false,
                    'Delete the task' => false,
                    'Start a new task with the same name' => false,
                ]],
                ['q' => 'Planning approval is late and you can\'t proceed. How do you report it?', 'options' => [
                    'Add a blocker on the task with the category "approval"' => true,
                    'Change the due date without telling anyone' => false,
                    'Email the client directly' => false,
                    'Leave the task in progress' => false,
                ]],
                ['q' => 'Which are task statuses in Thekan ERP? (Select all that apply.)', 'options' => [
                    'Pending' => true,
                    'Blocked' => true,
                    'Submitted for review' => true,
                    'Archived' => false,
                ]],
                ['q' => 'A submitted timesheet can be…', 'options' => [
                    'approved or rejected with a reason by your manager' => true,
                    'edited by the client' => false,
                    'ignored until the project ends' => false,
                    'posted to the general ledger by you' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 5: Thekan ERP — costs and budgets',
            'lessons' => [
                [
                    'title' => 'How a project budget works',
                    'content' => <<<'HTML'
<p>Every project has a budget in <strong>Project Budgets</strong>. Our clients hold us to it, so we track it closely.</p>
<ul>
  <li>A budget is made of <strong>budget lines</strong>, each with a cost category, description, quantity, unit cost and amount. Lines can be tied to a component, milestone or task.</li>
  <li>The cost categories are <em>materials, labour, logistics, subcontract, overhead, expense</em> and <em>other</em>.</li>
  <li>A budget moves from <em>draft</em> to <em>approved</em>. If it has to change — after an approved client change, say — it becomes <em>revised</em>, so the original stays on record. At the end it is <em>closed</em>.</li>
</ul>
<p>Tasks also carry an <strong>estimated cost</strong> and build up an <strong>actual cost</strong>. When your design decisions affect cost — a different stone, a larger glazing system — tell your project manager before the decision is made, not after.</p>
HTML,
                ],
                [
                    'title' => 'Recording costs correctly',
                    'content' => <<<'HTML'
<p>Costs are recorded in <strong>Project Costs</strong>. Many arrive automatically from other parts of the ERP — purchase orders, goods receipts, supplier bills, expenses, timesheets and stock issues. Others are entered manually.</p>
<h1>When you record or check a cost</h1>
<ul>
  <li>Use the right <strong>project</strong> and, where you can, the <strong>component, task or milestone</strong> it belongs to.</li>
  <li>Choose the right <strong>category</strong> so it lands on the right budget line.</li>
  <li>Enter a <strong>reference number</strong> (invoice, receipt or order) and a clear description.</li>
  <li>A cost starts as <em>draft</em>, is <em>posted</em> once checked, or is <em>cancelled</em> if it was wrong. Posted costs count against the budget.</li>
</ul>
<p>Day-to-day spending you make yourself — site transport, printing, samples — goes through <strong>Finance › Expenses</strong> or <strong>Petty Cash</strong> with a receipt, and is charged to the project.</p>
HTML,
                ],
                [
                    'title' => 'Watching budget against actual',
                    'content' => <<<'HTML'
<p>Overruns rarely arrive as one big surprise — they build up from small decisions nobody flagged.</p>
<ul>
  <li>On each project, compare the <strong>budget</strong> with the <strong>actual cost</strong> by category and by component.</li>
  <li><strong>Project Profitability</strong> shows how each project is doing against its budget and income.</li>
  <li>If a category is heading over budget, raise it with your project manager early, with the reason and what could bring it back.</li>
  <li>Never hide an overrun by recording costs against another project or category. The numbers are used to price future work and report to clients.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'You buy material samples for a client presentation. Where should the cost go?', 'options' => [
                    'Through Expenses or Petty Cash with the receipt, charged to the project' => true,
                    'Nowhere — samples are free' => false,
                    'Against any project with spare budget' => false,
                    'Into your timesheet' => false,
                ]],
                ['q' => 'Which are cost categories in Thekan ERP? (Select all that apply.)', 'options' => [
                    'Materials' => true,
                    'Subcontract' => true,
                    'Logistics' => true,
                    'Entertainment' => false,
                ]],
                ['q' => 'An approved client change increases the budget. What happens to the budget record?', 'options' => [
                    'It is revised, so the original stays on record' => true,
                    'The original is deleted and replaced' => false,
                    'Nothing — budgets never change' => false,
                    'A new project is created' => false,
                ]],
                ['q' => 'When does a project cost count against the budget?', 'options' => [
                    'Once it is posted' => true,
                    'As soon as it is typed in as a draft' => false,
                    'Only after the project closes' => false,
                    'Never' => false,
                ]],
                ['q' => 'A design choice you\'re considering will raise the cost. When should you tell the project manager?', 'options' => [
                    'Before the decision is made' => true,
                    'After the contractor has ordered it' => false,
                    'At handover' => false,
                    'Only if the client complains' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 6: Thekan ERP — document management',
            'lessons' => [
                [
                    'title' => 'Documents, versions and status',
                    'content' => <<<'HTML'
<p>Drawings, specifications, approvals, minutes and correspondence all live in <strong>Document Management</strong>. On a building, an out-of-date drawing on site can cost millions — document control is not optional.</p>
<h1>How documents work</h1>
<ul>
  <li>Every document has a <strong>document number</strong>, a <strong>category</strong> and a <strong>type</strong>. The type controls which file formats are allowed and whether an expiry date is required.</li>
  <li><strong>Versions:</strong> when a drawing is revised, upload it as a <em>new version</em> of the same document — never as a separate document and never by deleting the old one. Only one version is marked <em>latest</em>, and the history is kept.</li>
  <li><strong>Status:</strong> <em>draft</em> (work in progress, not for use), <em>active</em> (current and approved for use), <em>archived</em> (kept for the record) or <em>obsolete</em> (replaced — must not be used).</li>
  <li><strong>Dates:</strong> some documents — permits, insurances, approvals — have effective and expiry dates. Keep them up to date.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Confidentiality, linking and the audit trail',
                    'content' => <<<'HTML'
<h1>Confidentiality</h1>
<p>Set the right level on every document you upload:</p>
<ul>
  <li><em>Public</em> — cleared for anyone (rare on our projects).</li>
  <li><em>Internal</em> — Thekan staff only.</li>
  <li><em>Restricted</em> — the project team only: client drawings, cost reports, government documents.</li>
  <li><em>Confidential</em> — named people only: security layouts, sensitive client matters.</li>
</ul>
<h1>Linking</h1>
<p>Link each document to what it belongs to — the project, the task, the milestone — so it can be found from there. A drawing linked to its task is part of the evidence your supervisor reviews.</p>
<h1>The audit trail</h1>
<p>Thekan ERP records who created, changed and viewed each document, and what changed. It protects you: if there's ever a question about which drawing was issued, the record answers it.</p>
HTML,
                ],
                [
                    'title' => 'Our drawing and document conventions',
                    'content' => <<<'HTML'
<ul>
  <li><strong>Title</strong> every document clearly: project code, drawing or document number, and what it shows — for example <em>GOV-014 · A-201 · Ground floor plan</em>.</li>
  <li><strong>One document, many versions.</strong> Revise by uploading a new version; describe what changed in the notes.</li>
  <li><strong>Only active, latest documents go to site or the client.</strong> Before issuing, check the status and that you're sending the latest version.</li>
  <li><strong>Mark superseded documents obsolete</strong> so nobody uses them by mistake.</li>
  <li><strong>File everything that records a decision</strong> — minutes, sign-offs, approvals, instructions — and link it to the project.</li>
</ul>
<p>If you can't find the latest version of something, don't guess: ask the project manager. Building from the wrong drawing is the most avoidable mistake there is.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'You\'ve revised the ground floor plan. How do you put it in Thekan ERP?', 'options' => [
                    'Upload it as a new version of the same document' => true,
                    'Delete the old drawing and upload the new one' => false,
                    'Upload it as a new, separate document' => false,
                    'Email it to the contractor instead' => false,
                ]],
                ['q' => 'A drawing has been replaced by a newer one. What status should the old one have?', 'options' => [
                    'Obsolete' => true,
                    'Active' => false,
                    'Draft' => false,
                    'Public' => false,
                ]],
                ['q' => 'Which documents may go to site? (Select all that apply.)', 'options' => [
                    'Active, latest versions' => true,
                    'Drafts' => false,
                    'Obsolete drawings' => false,
                ]],
                ['q' => 'What confidentiality level suits a cost report for a government client?', 'options' => [
                    'Restricted (or higher)' => true,
                    'Public' => false,
                    'Internal' => false,
                    'None' => false,
                ]],
                ['q' => 'Why link a drawing to its task?', 'options' => [
                    'So it can be found from the task and reviewed as part of it' => true,
                    'It makes the file smaller' => false,
                    'It publishes it to the client' => false,
                    'It isn\'t possible' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 7: Putting it together',
            'lessons' => [
                [
                    'title' => 'A worked scenario: a change on a government project',
                    'content' => <<<'HTML'
<p>Let's bring it together. You're the architect on <em>GOV-014</em>, a new administrative block for a state government client. The design development drawings are active and the client signed off the stage last month.</p>
<h1>Monday — the request</h1>
<p>At a site meeting, the Commissioner's representative asks for the reception to be enlarged to receive visiting delegations. You note it, thank them, and say Thekan will come back with the effect on cost and programme. It goes in the minutes, which are filed in Document Management within 24 hours and linked to the project.</p>
<h1>Tuesday — assessing it</h1>
<p>Your project manager creates a task for the change study, with you as assignee and a supervisor as reviewer. You sketch two options. The structural engineer confirms one needs a new transfer beam, so you record a dependency on their check. The quantity surveyor prices both options against the budget lines.</p>
<h1>Wednesday — a blocker</h1>
<p>You need the existing services layout, which the services consultant hasn't issued. You log a blocker (category: <em>resource</em>) on the task. The project manager chases it and it's resolved the same afternoon.</p>
<h1>Friday — approval and update</h1>
<p>The client approves option A in writing, with its cost and time effect. You revise the affected drawings as new versions, mark the superseded sheets obsolete, complete the task checklist and submit the task for review. Your supervisor confirms it. The budget is revised to include the approved change, and your hours are on your timesheet.</p>
<p>Nothing was lost, nobody was surprised, and the record shows exactly what was asked, decided, drawn and paid for. That's how Thekan works.</p>
<p><strong>Next:</strong> the induction assessment. It covers every module — take your time and read each question carefully.</p>
HTML,
                ],
            ],
            // The induction assessment: this module's test.
            'final' => [
                'title' => 'Architect Induction Assessment',
                'slug' => 'architect-induction-assessment',
                'description' => '<p>The final assessment for the Thekan architect induction. It covers how we work, our clients, quality, and running projects in Thekan ERP.</p>',
                'instructions' => '<p>20 questions, 40 minutes. You need 80% to pass and have up to three attempts. Some questions have more than one correct answer — select all that apply. The exam is monitored: please stay in this window until you submit.</p>',
                'duration_minutes' => 40,
                'pass_percentage' => 80,
                'max_attempts' => 3,
                'questions' => [
                    ['q' => 'What must exist before design work begins?', 'options' => ['A written, agreed brief' => true, 'A contractor' => false, 'Construction drawings' => false, 'A site visit by the client' => false]],
                    ['q' => 'Who speaks for Thekan in formal communication with the client?', 'options' => ['The project manager, or whoever they direct' => true, 'Anyone on the team' => false, 'The contractor' => false, 'The most junior architect' => false]],
                    ['q' => 'A client asks for a change. Put the first step first: what do you do?', 'options' => ['Record exactly what was asked, by whom and when' => true, 'Instruct the contractor' => false, 'Revise the drawings' => false, 'Revise the budget' => false]],
                    ['q' => 'Which must be approved in writing before a change proceeds? (Select all that apply.)', 'options' => ['The change itself' => true, 'Its cost effect' => true, 'Its time effect' => true, 'The font used on the drawings' => false]],
                    ['q' => 'May you post photos of a client\'s residence on social media?', 'options' => ['Only with written approval from the project manager and the client' => true, 'Yes, if the building is finished' => false, 'Yes, if you don\'t name the client' => false, 'Yes, on a personal account' => false]],
                    ['q' => 'Which document should be marked confidential?', 'options' => ['A layout of a residence\'s security arrangements' => true, 'A published brochure' => false, 'An internal staff notice' => false, 'A public planning notice' => false]],
                    ['q' => 'What is the purpose of a hold point?', 'options' => ['To inspect work before it is covered up' => true, 'To pause the contract' => false, 'To store materials' => false, 'To hold a client meeting' => false]],
                    ['q' => 'Which are part of our pre-issue drawing checks? (Select all that apply.)', 'options' => ['Coordination with consultants' => true, 'Peer review' => true, 'Buildability' => true, 'Client signature on every sheet' => false]],
                    ['q' => 'Who finds the defects at snagging?', 'options' => ['We do — the client should never have to' => true, 'The client' => false, 'Nobody — defects are normal' => false, 'The next tenant' => false]],
                    ['q' => 'In Thekan ERP, which menu holds projects, tasks, costs, budgets and timesheets?', 'options' => ['Projects & Assets' => true, 'CRM' => false, 'Inventory Management' => false, 'Human Resources' => false]],
                    ['q' => 'Which task status means the work is waiting for your supervisor?', 'options' => ['Submitted for review' => true, 'Pending' => false, 'In progress' => false, 'Cancelled' => false]],
                    ['q' => 'A task requiring confirmation is rejected. What is recorded?', 'options' => ['The rejection and its reason, on the task history' => true, 'Nothing' => false, 'Only the date' => false, 'A fine' => false]],
                    ['q' => 'Which are blocker categories in Thekan ERP? (Select all that apply.)', 'options' => ['Approval' => true, 'Access' => true, 'Weather' => true, 'Holiday' => false]],
                    ['q' => 'What does a task dependency tell you?', 'options' => ['The task can\'t start until another one finishes' => true, 'Who is paying for it' => false, 'Which client asked for it' => false, 'The task\'s budget' => false]],
                    ['q' => 'How often should you submit your timesheet?', 'options' => ['Every week' => true, 'At the end of the project' => false, 'Only if asked' => false, 'Once a year' => false]],
                    ['q' => 'A budget line is changed after an approved client variation. The budget becomes…', 'options' => ['Revised, keeping the original on record' => true, 'Deleted' => false, 'Draft again' => false, 'Closed' => false]],
                    ['q' => 'Which cost status counts against the budget?', 'options' => ['Posted' => true, 'Draft' => false, 'Cancelled' => false, 'Pending' => false]],
                    ['q' => 'You notice a cost category heading over budget. What do you do?', 'options' => ['Raise it early with the project manager, with the reason and options' => true, 'Move costs to another project' => false, 'Wait until the end' => false, 'Change the category' => false]],
                    ['q' => 'How should a revised drawing be stored?', 'options' => ['As a new version of the same document, with what changed noted' => true, 'As a separate document with a new number' => false, 'By overwriting the file and deleting history' => false, 'On your laptop until it\'s final' => false]],
                    ['q' => 'Which documents can be issued to site? (Select all that apply.)', 'options' => ['Active documents' => true, 'The latest version' => true, 'Obsolete documents' => false, 'Drafts' => false]],
                ],
            ],
        ],
    ],

    // Workspace job titles. The course is recommended for the first one.
    // Recommended to these job titles (created by the seeder).
    'recommended_for' => ['Architect'],
];
