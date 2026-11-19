<?php

/*
 * "Projects, Site Installation & Construction in Thekan ERP" — content for
 * ThekanServicesSeeder.
 *
 * Global project-delivery practice (PMBOK/PRINCE2 essentials, scheduling,
 * cost control, site safety and quality), how Thekan delivers furniture
 * installation and construction jobs, and the Projects & Maintenance
 * module feature by feature: projects, components, milestones, tasks,
 * the timeline, timesheets, budgets, costs, invoices and profitability.
 *
 * Statuses, rules and calculations match Thekan ERP as built.
 *
 * Quiz format: 'q' => question, 'options' => [text => is_correct, ...].
 * More than one correct option makes it a "select all that apply" question.
 */

return [
    'test_prefix' => 'erp-projects',
    'test_label' => 'Projects',

    'course' => [
        'title' => 'Projects, Site Installation & Construction in Thekan ERP',
        'slug' => 'thekan-erp-projects',
        'description' => <<<'HTML'
<p>Every Thekan job ends up on a site: furniture we've designed and made being installed, or a building being constructed. This course is for the people who plan and run that work — and who answer for its time, quality and cost.</p>
<p>You'll learn what world-class project delivery looks like, how to plan, schedule and control a job, how to run a safe, high-quality site and installation, and how to do all of it in the <strong>Projects &amp; Maintenance</strong> module of Thekan ERP: projects, components, milestones, tasks, the timeline, timesheets, budgets, costs, invoices and the profitability dashboard.</p>
<ul>
<li><strong>Who it's for:</strong> project managers, site and installation supervisors, site engineers, quantity surveyors and architects.</li>
<li><strong>Before you start:</strong> complete <em>Thekan ERP Foundations</em>. This course opens once you've passed it.</li>
<li><strong>How it works:</strong> 7 modules, each ending with a test you must pass to unlock the next. The last module ends with the final assessment.</li>
<li><strong>To complete it:</strong> finish every lesson and score at least 80% on the final assessment. You'll receive a certificate.</li>
</ul>
HTML,
        'catalog' => [
            'subtitle' => 'Plan, build, install and control Thekan projects — to global standards, in Thekan ERP.',
            'category' => 'Thekan ERP',
            'level' => 'intermediate',
            'duration_minutes' => 240,
            'instructor_name' => 'Thekan Services Limited',
            'instructor_bio' => 'Prepared by Thekan for its project and site teams, based on recognised project-management, construction and site-safety practice and on how Thekan ERP is built.',
            'outcomes' => [
                'Apply the project lifecycle, baselines and change control to Thekan jobs',
                'Break a job into components, tasks and milestones, and schedule it with dependencies',
                'Run a safe, well-controlled site and furniture installation, from mobilisation to handover',
                'Set up and run a project in Thekan ERP, including creating it from a won quote',
                'Control labour and costs with timesheets, budgets and posted project costs',
                'Invoice project work correctly and read profitability, burn rate and project health',
            ],
        ],
    ],

    'modules' => [

        // ------------------------------------------------------------------
        [
            'title' => 'Module 1: Project delivery — global best practice',
            'lessons' => [
                [
                    'title' => 'What good project delivery looks like',
                    'preview' => true,
                    'content' => <<<'HTML'
<p>A <strong>project</strong> is temporary work with a defined start, end and result: a fitted-out office floor, a run of hotel bedrooms, a residence built and furnished. The world's main project-management frameworks — the Project Management Institute's <strong>PMBOK</strong> and the UK-born <strong>PRINCE2</strong> — describe them differently, but agree on the essentials.</p>
<h1>The five stages of every project</h1>
<ol>
<li><strong>Initiate</strong> — confirm what the client wants, why, the budget and the deadline. Is it worth doing?</li>
<li><strong>Plan</strong> — break the work down, schedule it, estimate the cost, identify risks and agree who does what.</li>
<li><strong>Execute</strong> — design, procure, manufacture, deliver, install and build.</li>
<li><strong>Monitor and control</strong> — compare progress and cost against the plan every week, and act on the differences.</li>
<li><strong>Close</strong> — hand over, settle the final account, archive the records and capture the lessons learned.</li>
</ol>
<h1>The triple constraint</h1>
<p>Every project balances <strong>scope</strong> (what we deliver, to what quality), <strong>time</strong> and <strong>cost</strong>. Change one and at least one of the others moves. When a client adds three more wardrobes, either the finish date, the price or both must change — and that must be agreed, not absorbed silently.</p>
<h1>Principles shared by the best project firms</h1>
<ul>
<li><strong>Plan before you spend.</strong> No significant spend before there is an approved budget and an agreed scope.</li>
<li><strong>Baseline, then measure.</strong> Once the plan and budget are approved, they are the baseline. Progress is always measured against it.</li>
<li><strong>Manage by exception.</strong> Managers don't need every detail — they need to know early when something is drifting outside tolerance.</li>
<li><strong>Control change.</strong> Every change to scope, time or cost is requested, assessed, approved and recorded.</li>
<li><strong>Learn.</strong> Each project's lessons make the next estimate and plan better.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Thekan\'s project types and lifecycle',
                    'content' => <<<'HTML'
<p>Thekan delivers several kinds of project, often combined on one job:</p>
<ul>
<li><strong>Furniture supply and installation</strong> — designed in-house, manufactured in our workshop or bought in, delivered and installed on site (kitchens, wardrobes, joinery, loose furniture, office fit-out).</li>
<li><strong>Building and construction</strong> — new build, extensions and renovation, with our teams and subcontractors.</li>
<li><strong>Design and build</strong> — we design the space and then construct and furnish it.</li>
<li><strong>Maintenance</strong> — repairs, servicing and aftercare for completed jobs (the menu calls the module <em>Projects &amp; Maintenance</em>).</li>
<li><strong>Internal projects</strong> — improving our own facilities or systems, tracked for cost and accountability but not billed.</li>
</ul>
<h1>The Thekan job lifecycle</h1>
<ol>
<li><strong>Enquiry and survey</strong> — brief, site visit, measurements and constraints.</li>
<li><strong>Design and pricing</strong> — concept, BoQ and sales quote.</li>
<li><strong>Award</strong> — the quote is won and the project is created, with a starting budget from the quote.</li>
<li><strong>Detailed design</strong> — shop drawings and specifications, approved by the client before manufacture.</li>
<li><strong>Procurement and manufacture</strong> — materials ordered, items made, quality checked in the workshop.</li>
<li><strong>Site preparation</strong> — the site made ready: access, power, finished walls and floors, storage, safety.</li>
<li><strong>Delivery and installation / construction</strong> — the work on site.</li>
<li><strong>Snagging and handover</strong> — defects fixed, client sign-off, handover documents.</li>
<li><strong>Closeout and aftercare</strong> — final account, retention, warranty and lessons learned.</li>
</ol>
<blockquote><strong>Furniture lead times drive the programme.</strong> A kitchen can't be installed until it's been drawn, approved, cut, edged, assembled, finished and delivered. Plan backwards from the installation date.</blockquote>
HTML,
                ],
                [
                    'title' => 'Roles, responsibilities and governance',
                    'content' => <<<'HTML'
<p>Projects fail most often because nobody is quite sure who decides. A <strong>RACI</strong> chart — <strong>R</strong>esponsible (does it), <strong>A</strong>ccountable (owns the outcome; one person only), <strong>C</strong>onsulted, <strong>I</strong>nformed — makes it explicit.</p>
<h1>Typical roles on a Thekan project</h1>
<ul>
<li><strong>Project Manager</strong> — accountable for the project's scope, programme, budget, quality and client relationship. Owns the project record in the ERP, approves budgets and timesheets, and reviews cost and profitability.</li>
<li><strong>Architect / Designer</strong> — design intent, drawings, specifications and design changes.</li>
<li><strong>Quantity Surveyor</strong> — measurement, cost planning, valuations, variations and the final account.</li>
<li><strong>Site Supervisor / Installation Supervisor</strong> — runs the site day to day: safety, quality, sequencing, labour and subcontractors. Supervises and confirms site tasks.</li>
<li><strong>Site Engineer</strong> — setting out, technical checks and inspections.</li>
<li><strong>Installers, tradespeople and subcontractors</strong> — do the work, log time and raise blockers.</li>
<li><strong>Procurement, stores and workshop</strong> — supply materials and manufactured items on time.</li>
<li><strong>Finance</strong> — invoicing, collections and cost accounting.</li>
</ul>
<h1>Governance in practice</h1>
<ul>
<li>A weekly project review: progress against the timeline, blockers, budget against actual, risks and changes.</li>
<li>Clear approval limits: who can approve which budgets, costs, changes and invoices.</li>
<li>Records in the ERP, not in people's heads: if the project manager is away, the next person can pick it up from the project record.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What are the three sides of the "triple constraint"?', 'options' => [
                    'Scope, time and cost' => true,
                    'Design, colour and finish' => false,
                    'Client, supplier and bank' => false,
                    'Tasks, chat and email' => false,
                ]],
                ['q' => 'A client asks for three extra wardrobes mid-project. What is best practice?', 'options' => [
                    'Treat it as a change: assess, agree the effect on price and programme, approve and record it' => true,
                    'Absorb it quietly to keep the client happy' => false,
                    'Refuse all changes' => false,
                    'Do it and invoice later without telling the client' => false,
                ]],
                ['q' => 'In a RACI chart, how many people are Accountable for a deliverable?', 'options' => [
                    'One' => true,
                    'Everyone on the team' => false,
                    'At least three' => false,
                    'None' => false,
                ]],
                ['q' => 'Why should a furniture installation programme be planned backwards from the installation date?', 'options' => [
                    'Design approval, manufacture and delivery lead times must all fit before installation' => true,
                    'Because installers prefer it' => false,
                    'Because the ERP requires it' => false,
                    'It shouldn\'t — start installing immediately' => false,
                ]],
                ['q' => 'Select ALL the stages of the project lifecycle.', 'options' => [
                    'Initiate' => true,
                    'Plan' => true,
                    'Monitor and control' => true,
                    'Forget' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 2: Planning and scheduling the work',
            'lessons' => [
                [
                    'title' => 'Breaking the job down: the work breakdown structure',
                    'content' => <<<'HTML'
<p>A <strong>work breakdown structure (WBS)</strong> splits the whole job into smaller and smaller pieces until each piece can be estimated, assigned and checked. It is the backbone of the schedule, the budget and the cost reports.</p>
<h1>How to build a good WBS</h1>
<ul>
<li><strong>Top level: components.</strong> The big parts of the job — for example by area (Ground floor, Block B, Master suite) or by package (Kitchens, Wardrobes, Joinery, Electrical, Finishes).</li>
<li><strong>Next level: tasks.</strong> The work to deliver each component: survey, shop drawings, manufacture, delivery, installation, snagging.</li>
<li><strong>Lowest level: sub-tasks</strong> small enough for one person or crew to finish in a few days.</li>
<li><strong>The 100% rule:</strong> the WBS covers all the work — and nothing that isn't in scope.</li>
<li>Name items by their <strong>deliverable</strong>, not by a vague activity.</li>
</ul>
<h1>An example: hotel bedroom fit-out, Floor 3</h1>
<ul>
<li>Component: <strong>Floor 3 — Bedrooms 301–320</strong>
<ul>
<li>Shop drawings — headboards and wardrobes (approved by client)</li>
<li>Manufacture — 20 headboards, 20 wardrobes</li>
<li>Deliver and store on Floor 3</li>
<li>Install — rooms 301–310, then 311–320</li>
<li>Snag and hand over — rooms 301–320</li>
</ul>
</li>
</ul>
<p>In Thekan ERP, components, tasks and sub-tasks (child tasks with a parent) map directly onto this structure.</p>
HTML,
                ],
                [
                    'title' => 'Scheduling: dependencies and the critical path',
                    'content' => <<<'HTML'
<p>A schedule (programme) puts the tasks in order and on dates. Global practice uses the <strong>critical path method</strong>:</p>
<ol>
<li>List the tasks and estimate how long each takes.</li>
<li>Set the <strong>dependencies</strong> — which tasks must finish before others can start. The most common type is <strong>finish-to-start</strong>: "fit worktops" can't start until "install base units" finishes.</li>
<li>The longest chain of dependent tasks from start to finish is the <strong>critical path</strong>. Any delay on it delays the whole project; tasks off it have some <strong>float</strong> (spare time).</li>
</ol>
<h1>Common dependencies on Thekan jobs</h1>
<ul>
<li>Client approval of shop drawings → manufacture.</li>
<li>Manufacture and finishing → delivery → installation.</li>
<li>Wet trades (plaster, screed, tiling) dry → joinery installation, to prevent swelling and damage.</li>
<li>First-fix electrical and plumbing → closing walls → kitchen installation → second fix.</li>
<li>Installation → snagging → cleaning → handover.</li>
</ul>
<h1>Scheduling good practice</h1>
<ul>
<li>Estimate with the people who'll do the work, using past jobs' actual hours.</li>
<li>Include lead times for procurement, manufacture and client approvals — these are often the critical path, not the site work.</li>
<li>Build in realistic allowances for weather, access and inspections.</li>
<li>Update the schedule every week and look first at the critical path.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Milestones and payment stages',
                    'content' => <<<'HTML'
<p>A <strong>milestone</strong> is a significant point in the project — a deliverable, a decision or a stage completed — with no duration of its own. Milestones give clients, managers and finance simple checkpoints:</p>
<ul>
<li><strong>Control milestones:</strong> "Shop drawings approved", "Manufacture complete", "Site ready for installation", "Practical completion".</li>
<li><strong>Payment milestones:</strong> many contracts pay in stages — a deposit, then on delivery, on installation and at completion, often with a <strong>retention</strong> held back until defects are fixed.</li>
</ul>
<h1>Good milestones</h1>
<ul>
<li>Are objectively measurable — everyone can agree whether they've been reached.</li>
<li>Have one owner and a target date.</li>
<li>Line up with the contract's payment terms, so invoicing follows delivery without argument.</li>
<li>Carry a weighting that reflects their share of the project, so progress can be measured fairly.</li>
</ul>
<blockquote><strong>Cash flow matters.</strong> On many jobs Thekan pays for materials and labour long before the client pays us. Clear payment milestones — reached and invoiced promptly — keep the project, and the company, funded.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What is the "100% rule" of a work breakdown structure?', 'options' => [
                    'The WBS covers all the work in scope — and nothing outside it' => true,
                    'Every task must be 100% complete before the project starts' => false,
                    'The project must make a 100% margin' => false,
                    'Each task takes 100 hours' => false,
                ]],
                ['q' => 'What is the critical path?', 'options' => [
                    'The longest chain of dependent tasks; any delay on it delays the project' => true,
                    'The shortest route to site' => false,
                    'The list of the most expensive tasks' => false,
                    'The client\'s approval process' => false,
                ]],
                ['q' => 'Why shouldn\'t joinery be installed before wet trades have dried?', 'options' => [
                    'Moisture can swell and damage the joinery' => true,
                    'It looks untidy' => false,
                    'It is faster to install later' => false,
                    'There is no reason' => false,
                ]],
                ['q' => 'Which of these is a good, measurable milestone?', 'options' => [
                    '"Shop drawings approved by the client"' => true,
                    '"Make good progress"' => false,
                    '"Work on the kitchen"' => false,
                    '"Try to finish soon"' => false,
                ]],
                ['q' => 'Select ALL the common dependencies on a Thekan furniture job.', 'options' => [
                    'Client approval of shop drawings before manufacture' => true,
                    'Manufacture before delivery' => true,
                    'Installation before snagging' => true,
                    'Handover before installation' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 3: Running a safe, high-quality site',
            'lessons' => [
                [
                    'title' => 'Mobilisation and site safety',
                    'content' => <<<'HTML'
<p>Every person on a Thekan site goes home safe. That isn't a slogan — it's the first measure of a well-run project. Good practice worldwide (for example <strong>ISO 45001</strong> for occupational health and safety) is built on the same basics.</p>
<h1>Before work starts: mobilisation</h1>
<ul>
<li><strong>Site induction</strong> for everyone, including subcontractors and delivery drivers: hazards, emergency exits, first aid, PPE and site rules.</li>
<li><strong>Risk assessment and method statement (RAMS)</strong> for each significant activity: what could go wrong, and how we'll do the work safely.</li>
<li><strong>Access and logistics:</strong> delivery times, routes, lifting, storage areas and protection of finished surfaces.</li>
<li><strong>Welfare:</strong> water, toilets, a rest area, first-aid kit and fire extinguishers.</li>
</ul>
<h1>Every day</h1>
<ul>
<li><strong>PPE</strong>: safety boots, hard hats where required, gloves, eye and ear protection, dust masks when cutting or sanding.</li>
<li><strong>Toolbox talk</strong>: a short morning briefing on the day's work and its hazards.</li>
<li><strong>Housekeeping</strong>: clear walkways, offcuts and packaging removed, cables managed.</li>
<li><strong>Permits</strong> for high-risk work such as hot work, work at height and electrical isolation.</li>
<li><strong>Power tools</strong> inspected, guarded and used only by trained people.</li>
</ul>
<h1>Higher-risk activities on our jobs</h1>
<ul>
<li>Working at height (ladders, scaffolds, mobile towers) — the leading cause of serious injury in construction.</li>
<li>Manual handling of heavy panels, worktops and furniture — plan two-person lifts and use trolleys.</li>
<li>Electrical work and live services in walls — check before drilling.</li>
<li>Dust and fumes from cutting, sanding, adhesives and finishes — ventilate and protect.</li>
</ul>
<blockquote><strong>Stop the job.</strong> Anyone on site may stop work they believe is unsafe. Nobody will be criticised for stopping a job in good faith.</blockquote>
HTML,
                ],
                [
                    'title' => 'Quality: inspections, hold points and snagging',
                    'content' => <<<'HTML'
<p>Quality isn't checked in at the end — it's built in at every step. Contractors following <strong>ISO 9001</strong> do this with an <strong>Inspection and Test Plan (ITP)</strong>: a list of the checks each activity needs, who carries them out, and what evidence is kept.</p>
<h1>Key ideas</h1>
<ul>
<li><strong>Hold points</strong> — work stops until an inspection is passed (for example, the client signs off a sample door finish before production continues; services are inspected before walls are closed).</li>
<li><strong>Witness points</strong> — the client or engineer is invited to watch, but work can continue if they don't attend.</li>
<li><strong>First-off inspection</strong> — check the first unit made or installed thoroughly before repeating it 50 times.</li>
<li><strong>Evidence</strong> — photos, checklists and signed records, kept on the project.</li>
</ul>
<h1>Snagging and handover</h1>
<ul>
<li>Inspect each area against the drawings and specification before the client does. Find our own defects first.</li>
<li>Record each snag with its location (room), a photo, the trade responsible and a due date.</li>
<li>Fix, re-inspect and close each snag.</li>
<li>Hand over with the documents the client needs: as-built drawings, product data, care and maintenance guides, warranties and certificates.</li>
</ul>
<p>In Thekan ERP, <strong>checklists</strong> on tasks act as the ITP (a required checklist works like a hold point — the task can't be submitted until it passes), and photos attached to tasks appear in the project's <strong>Gallery</strong>.</p>
HTML,
                ],
                [
                    'title' => 'Furniture installation: doing it right first time',
                    'content' => <<<'HTML'
<p>Furniture installation is where design, manufacture and site come together — and where a small error becomes visible to the client.</p>
<h1>Before delivery</h1>
<ul>
<li>Confirm the site is <strong>ready</strong>: walls and floors finished and dry, services in place, rooms clean, access cleared, secure storage available.</li>
<li>Re-check critical site dimensions against the shop drawings.</li>
<li>Agree the delivery date, time, route and lifting plan with the site.</li>
</ul>
<h1>Delivery and storage</h1>
<ul>
<li>Check every item against the delivery note; record any damage or shortage immediately, with photos.</li>
<li>Store items flat, dry and protected, in the room or area where they'll be installed, in installation order.</li>
</ul>
<h1>Installation</h1>
<ul>
<li>Protect floors, walls and finished items before you start.</li>
<li>Set out and level carefully — a cabinet run that's out of level at the start is out of level at the end.</li>
<li>Fix securely to suitable walls and fixings; tall units are anchored against tipping.</li>
<li>Adjust doors, drawers and hinges for even gaps and smooth operation.</li>
<li>Clean as you go, remove packaging daily, and leave each room clean and ready for inspection.</li>
</ul>
<h1>Damage and defects</h1>
<p>Report damage or manufacturing defects the same day, with photos, so replacements can be made without delaying handover. In the ERP, raise a <strong>blocker</strong> (category <em>material</em>) on the installation task.</p>
HTML,
                ],
                [
                    'title' => 'Construction site management: diary, subcontractors and materials',
                    'content' => <<<'HTML'
<h1>The daily record</h1>
<p>Good contractors keep a daily site record: who was on site, what was done, deliveries, inspections, weather, delays and instructions received. If there is ever a dispute about delay or cost, the daily record is the evidence. At Thekan, the ERP holds most of it: task progress and time logs, blockers (including <em>weather</em> and <em>access</em>), timesheets, and photos and documents on the project and its tasks.</p>
<h1>Managing subcontractors</h1>
<ul>
<li>Agree scope, price, programme and quality standard in writing before they start.</li>
<li>Induct them on site safety and Thekan's standards.</li>
<li>Assign them to their tasks in the ERP, so their work is planned and tracked with ours.</li>
<li>Measure and approve their work before their invoices are paid, and record their costs against the right task.</li>
</ul>
<h1>Materials control</h1>
<ul>
<li>Order to the programme, not too early (damage, theft, cash tied up) and not too late (idle crews).</li>
<li>Check deliveries, store securely, and issue materials against the job and task.</li>
<li>Reconcile materials used against materials budgeted. Large differences mean waste, theft or a wrong estimate — all need explaining.</li>
</ul>
<h1>Site instructions and variations</h1>
<p>Any instruction that changes scope — from the client, architect or engineer — is recorded in writing the same day, with its effect on cost and time. Unrecorded verbal instructions are the most common cause of unpaid work.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What is a RAMS?', 'options' => [
                    'A risk assessment and method statement for a work activity' => true,
                    'A type of power tool' => false,
                    'A payment certificate' => false,
                    'A drawing register' => false,
                ]],
                ['q' => 'What is a hold point in an Inspection and Test Plan?', 'options' => [
                    'Work stops until an inspection is passed' => true,
                    'A place to store tools' => false,
                    'A break in the working day' => false,
                    'An optional inspection the client may skip' => false,
                ]],
                ['q' => 'Several wardrobe panels arrive damaged on site. What should you do?', 'options' => [
                    'Record it against the delivery with photos the same day and raise a material blocker on the installation task' => true,
                    'Install them and hope the client doesn\'t notice' => false,
                    'Throw them away and say nothing' => false,
                    'Wait until handover to mention it' => false,
                ]],
                ['q' => 'The client\'s architect verbally asks for an extra shelf in every wardrobe. What is best practice?', 'options' => [
                    'Record the instruction in writing the same day with its effect on cost and time' => true,
                    'Do it without recording anything' => false,
                    'Ignore it' => false,
                    'Agree to do it for free' => false,
                ]],
                ['q' => 'Select ALL the checks before furniture is delivered to site.', 'options' => [
                    'Walls and floors finished and dry' => true,
                    'Critical dimensions re-checked against shop drawings' => true,
                    'Secure storage and access agreed' => true,
                    'Snagging list closed' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 4: Thekan ERP — setting up a project',
            'lessons' => [
                [
                    'title' => 'Creating a project — and creating one from a won quote',
                    'content' => <<<'HTML'
<p>Projects live under <strong>Projects &amp; Maintenance → Projects</strong>. The project record is the parent of everything else: components, tasks, milestones, timesheets, costs, budgets, invoices and documents.</p>
<h1>Creating from a won quote (recommended)</h1>
<p>When a sales quote is marked <strong>won</strong>, the Sales team can create a project from it. The ERP then:</p>
<ul>
<li>creates the project, named from the site location and quote number, linked to the client and the quote, with status <strong>Planned</strong>;</li>
<li>creates a <strong>draft budget</strong> — "Initial budget (from the quote number)" — with one line for each quote line, grouped by the quote's sections.</li>
</ul>
<p>Only won quotes can become projects, and each quote can create only one project. Starting from the quote means execution is measured against what we sold from day one.</p>
<h1>Creating a project directly</h1>
<ul>
<li><strong>Project name</strong> (required) and <strong>code</strong> (generated automatically if left blank).</li>
<li><strong>Company</strong>, plus any other companies the project is shared with — so people from those companies can be assigned.</li>
<li><strong>Client</strong>, <strong>location</strong> (the site), <strong>project manager</strong> and <strong>team members</strong>.</li>
<li><strong>Priority</strong> — low, medium, high or critical.</li>
<li><strong>Start and end dates</strong>, a description and notes.</li>
</ul>
<h1>Project statuses</h1>
<ol>
<li><strong>Draft</strong> — being set up.</li>
<li><strong>Planned</strong> — approved to go ahead; planning underway.</li>
<li><strong>Active</strong> — work in progress.</li>
<li><strong>On hold</strong> — paused (for example, awaiting client decisions or payment).</li>
<li><strong>Completed</strong> or <strong>Cancelled</strong>.</li>
</ol>
<h1>The project page</h1>
<p>Open a project to see its tabs: <strong>Overview</strong>, <strong>Components</strong>, <strong>Tasks</strong>, <strong>Timeline</strong>, <strong>Budgets</strong>, <strong>Costs</strong>, <strong>Milestones</strong>, <strong>Invoices</strong>, <strong>Quotes &amp; BoQs</strong>, <strong>Documents</strong> and <strong>Gallery</strong>. You can manage most of the project from here without leaving the page.</p>
HTML,
                ],
                [
                    'title' => 'Components and milestones',
                    'content' => <<<'HTML'
<h1>Components — the top of your WBS</h1>
<p>On the project's <strong>Components</strong> tab, create the major parts of the job — by area or package (for example "Block B — Kitchens", "Ground floor — Reception joinery"). Each has a name, an optional code, a status (<strong>planning</strong>, <strong>active</strong>, <strong>on hold</strong>, <strong>completed</strong>, <strong>cancelled</strong>), an estimated cost and a sort order.</p>
<p>Tasks and costs can be attached to a component, so you can see progress and spend per area or package — not just for the whole project.</p>
<h1>Milestones</h1>
<p>On the <strong>Milestones</strong> tab (or <strong>Project Milestones</strong> in the menu), create the project's checkpoints:</p>
<ul>
<li><strong>Name</strong> and code — "Shop drawings approved", "Floor 3 installed", "Practical completion".</li>
<li><strong>Owner</strong> — the one person accountable.</li>
<li><strong>Target date</strong>, <strong>priority</strong> and <strong>progress %</strong>.</li>
<li><strong>Weight %</strong> — the milestone's share of the whole project, for example 10% for design approval and 40% for installation.</li>
<li><strong>Status</strong> — pending, in progress, completed or cancelled.</li>
</ul>
<p>Milestones appear on the project timeline, and can be moved there. Moving a milestone changes only its own target date — it doesn't move any tasks. Completed milestones count towards the project's progress, and milestones can be used as invoice lines for stage payments.</p>
HTML,
                ],
                [
                    'title' => 'Tasks, assignees, supervisors and contractors',
                    'content' => <<<'HTML'
<p>Create project tasks from the project's <strong>Tasks</strong> tab or <strong>Project Tasks</strong> in the menu. Everything you learned in Foundations applies; here is what matters for projects.</p>
<h1>Setting up a project task</h1>
<ul>
<li><strong>Component</strong> — which part of the job it belongs to.</li>
<li><strong>Parent task</strong> — to create sub-tasks under a larger task.</li>
<li><strong>Name</strong> and <strong>description</strong> — what done looks like.</li>
<li><strong>Assignees</strong> — who does it; <strong>supervisors</strong> — who reviews and confirms it. You can choose people from any company the project is shared with.</li>
<li><strong>Contractors</strong> — attach a subcontractor (with their role) when they do the work.</li>
<li><strong>Start and due dates</strong>, <strong>priority</strong>, <strong>estimated hours</strong> and <strong>estimated cost</strong>.</li>
<li><strong>Confirmation required</strong> — leave this on for site work, so a supervisor signs it off.</li>
</ul>
<h1>Quality and sequence built in</h1>
<ul>
<li>Add the task's <strong>checklists</strong> — for example a required "Installation quality check" graded at 90%.</li>
<li>Add its <strong>dependencies</strong> — the tasks that must be completed first.</li>
</ul>
<h1>Who gets told</h1>
<p>New assignees and supervisors are notified (bell and email) when they're added. When a task is completed, its assignees and supervisors are notified.</p>
<blockquote><strong>Estimate hours and cost on each task.</strong> Without estimates, there's nothing to compare actuals against — and the next job's estimate will be a guess too.</blockquote>
HTML,
                ],
                [
                    'title' => 'The timeline: scheduling with dependencies',
                    'content' => <<<'HTML'
<p>The project's <strong>Timeline</strong> tab shows the schedule as a Gantt chart: each task as a bar from its start to its due date, linked by its dependencies, with milestones as markers.</p>
<h1>Rescheduling</h1>
<ul>
<li>Drag a task's bar to change its dates (if your role allows it).</li>
<li>When you move a task, the ERP <strong>cascades</strong> the change to the tasks that depend on it, so the sequence stays valid — and tells you how many dependent tasks moved.</li>
<li>Moving a milestone moves only that milestone.</li>
<li>Every reschedule is recorded in the audit trail.</li>
</ul>
<h1>Dependency rules</h1>
<ul>
<li>A task can't depend on itself, and the ERP refuses a dependency that would create a loop.</li>
<li>A task whose predecessor isn't completed shows as <strong>blocked</strong>.</li>
</ul>
<h1>Using the timeline well</h1>
<ul>
<li>Build the timeline before work starts, and agree it with the team — it becomes your schedule baseline.</li>
<li>Review it weekly. Look first at the critical path and at tasks that are overdue or blocked.</li>
<li>When a delay is real, reschedule honestly and tell the people affected. A timeline that doesn't reflect reality is worse than none.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What does the ERP create when a project is created from a won sales quote?', 'options' => [
                    'A Planned project linked to the client and quote, and a draft budget with a line per quote line' => true,
                    'Only an invoice' => false,
                    'A completed project' => false,
                    'A purchase order for every line' => false,
                ]],
                ['q' => 'Can a quote that is still being negotiated be turned into a project?', 'options' => [
                    'No — only won quotes can, and only once per quote' => true,
                    'Yes, any quote can, as many times as needed' => false,
                    'Yes, but only by the client' => false,
                    'Only if it has no lines' => false,
                ]],
                ['q' => 'You drag a task to a later date on the timeline. What happens to the tasks that depend on it?', 'options' => [
                    'They are moved too (cascaded) so the sequence stays valid' => true,
                    'Nothing — they keep their dates' => false,
                    'They are deleted' => false,
                    'They are marked completed' => false,
                ]],
                ['q' => 'Why attach tasks and costs to a project component?', 'options' => [
                    'To see progress and spend per area or package, not just for the whole project' => true,
                    'Components are required for every chat' => false,
                    'It hides the costs from the client' => false,
                    'It has no effect' => false,
                ]],
                ['q' => 'Select ALL the project statuses in Thekan ERP.', 'options' => [
                    'Planned' => true,
                    'Active' => true,
                    'On hold' => true,
                    'Posted' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 5: Thekan ERP — running the work',
            'lessons' => [
                [
                    'title' => 'The site team\'s day in the ERP',
                    'content' => <<<'HTML'
<p>The site is where the ERP earns its keep — if the site team keeps it current. Here's the daily routine for supervisors and installers.</p>
<h1>Start of day</h1>
<ol>
<li>Open your <strong>Dashboard</strong>: your project tasks (pending, in progress, blocked) and, for supervisors, the tasks you supervise.</li>
<li>Check the project's <strong>Timeline</strong> for today's and tomorrow's work, and what's blocked.</li>
<li>Toolbox talk: today's tasks, their hazards and their checklists.</li>
</ol>
<h1>During the day</h1>
<ul>
<li><strong>Start</strong> each task when work begins.</li>
<li>Record <strong>time logs</strong> against the task.</li>
<li>Tick <strong>checklist</strong> items as each check is done — honestly.</li>
<li>Raise a <strong>blocker</strong> the moment something stops the work: missing or damaged material, a client approval, access, weather or resources. Resolve it with a note when it's cleared.</li>
<li>Attach <strong>photos</strong> to the task — before, during and after, and of any damage or issue. They appear in the project's Gallery.</li>
</ul>
<h1>End of day</h1>
<ul>
<li><strong>Submit</strong> tasks that are truly finished and pass their checklists.</li>
<li>Supervisors: <strong>confirm</strong> or <strong>reject</strong> submitted work — with a clear reason if rejecting.</li>
<li>Make sure everyone's <strong>timesheet</strong> for the day is entered.</li>
</ul>
<blockquote><strong>Five minutes a day</strong> keeping the ERP current saves hours of status meetings, phone calls and arguments about what happened.</blockquote>
HTML,
                ],
                [
                    'title' => 'Timesheets: capturing labour',
                    'content' => <<<'HTML'
<p>Labour is usually one of the largest costs on a Thekan job. <strong>Project Timesheets</strong> capture who worked on which project — and, ideally, which task or milestone — for how long, and at what cost.</p>
<h1>Entering a timesheet</h1>
<ul>
<li><strong>Project</strong> (required), and the <strong>task</strong> or <strong>milestone</strong> where possible.</li>
<li><strong>Employee</strong> and <strong>date</strong>.</li>
<li><strong>Hours</strong>, or a start and end time.</li>
<li><strong>Hourly rate</strong> — the cost of the labour.</li>
<li><strong>Billable?</strong> — whether the time can be charged to the client, with billable hours and the billing rate.</li>
<li>A description of the work done.</li>
</ul>
<h1>The approval flow</h1>
<ol>
<li><strong>Draft</strong> — entered and editable.</li>
<li><strong>Submitted</strong> — sent for approval (a draft or a rejected entry can be submitted).</li>
<li><strong>Approved</strong> — accepted by someone with approval permission. Or <strong>rejected</strong> with a reason, to be corrected and resubmitted.</li>
</ol>
<p>When a timesheet is <strong>approved</strong>, the ERP <strong>automatically creates a posted labour cost</strong> on the project (hours × hourly rate). You don't enter labour costs separately — so approving accurate timesheets is how labour reaches the budget-versus-actual and profitability figures.</p>
<h1>Good practice</h1>
<ul>
<li>Enter time daily, against the right task.</li>
<li>Classify billable and non-billable time correctly; time-and-materials jobs are billed from it.</li>
<li>Approvers: check hours against the task's progress and time logs before approving.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Documents, photos and progress',
                    'content' => <<<'HTML'
<h1>Project documents</h1>
<p>The project's <strong>Documents</strong> tab holds the controlled documents for the job: contract, drawings, specifications, approvals, RAMS, inspection records, certificates and handover documents. Attach documents to the project or to a specific task; upload revisions as new versions, and keep confidentiality levels right.</p>
<h1>The Gallery</h1>
<p>The <strong>Gallery</strong> tab gathers every image attached to the project or any of its tasks into one searchable view, grouped by document category, showing which task each photo came from. Use it for progress reports, client updates, snagging, and evidence in any dispute.</p>
<h1>Quotes &amp; BoQs</h1>
<p>The <strong>Quotes &amp; BoQs</strong> tab shows the commercial documents behind the project, so the site team can check what was actually sold and priced before doing extra work.</p>
<h1>Progress</h1>
<ul>
<li>Task progress comes from task statuses and progress percentages; completed tasks count as complete.</li>
<li>Milestone progress comes from milestone status and progress percentages.</li>
<li>Keep both current — they feed the project's progress and its health score.</li>
</ul>
<blockquote><strong>Photos are evidence.</strong> A dated photo of a site that wasn't ready, or of damage on delivery, can be worth more than any letter.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What happens when a project timesheet is approved?', 'options' => [
                    'A posted labour cost (hours × hourly rate) is created on the project automatically' => true,
                    'Nothing — labour costs must be typed in separately' => false,
                    'The employee is paid immediately' => false,
                    'The project is completed' => false,
                ]],
                ['q' => 'Which timesheet entries can be submitted for approval?', 'options' => [
                    'Draft or rejected entries' => true,
                    'Only approved entries' => false,
                    'Any entry, as many times as you like' => false,
                    'Only entries older than a month' => false,
                ]],
                ['q' => 'Where do photos attached to a project\'s tasks appear together?', 'options' => [
                    'On the project\'s Gallery tab' => true,
                    'Only in chat' => false,
                    'In the payroll run' => false,
                    'They are not kept' => false,
                ]],
                ['q' => 'A delivery of worktops hasn\'t arrived and the installers are waiting. What should the supervisor do in the ERP?', 'options' => [
                    'Raise a blocker on the installation task with category Material' => true,
                    'Submit the task as completed' => false,
                    'Delete the task' => false,
                    'Do nothing until the end of the week' => false,
                ]],
                ['q' => 'Select ALL the things a site supervisor should do at the end of each day.', 'options' => [
                    'Submit tasks that are finished and pass their checklists' => true,
                    'Confirm or reject submitted work, with reasons for rejections' => true,
                    'Make sure the team\'s timesheets are entered' => true,
                    'Mark unfinished tasks as completed' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 6: Budgets and cost control',
            'lessons' => [
                [
                    'title' => 'Project budgets: the cost baseline',
                    'content' => <<<'HTML'
<p>A <strong>budget</strong> is the planned cost of the project, broken down so you can control it. Good practice is to approve the budget before significant spending starts, and then treat it as the <strong>baseline</strong> every cost is measured against.</p>
<h1>Budgets in Thekan ERP</h1>
<p>Create budgets on the project's <strong>Budgets</strong> tab or under <strong>Project Budgets</strong>. A budget has a name, an optional code, a version number, dates, a currency and one or more <strong>lines</strong>. Each line has:</p>
<ul>
<li>a <strong>cost category</strong> — <strong>materials</strong>, <strong>labour</strong>, <strong>logistics</strong>, <strong>subcontract</strong>, <strong>overhead</strong>, <strong>expense</strong> or <strong>other</strong>;</li>
<li>optionally the <strong>task</strong> or <strong>milestone</strong> it funds;</li>
<li>a description, a <strong>quantity</strong> and a <strong>unit cost</strong> (giving the budget amount).</li>
</ul>
<h1>Budget statuses</h1>
<ol>
<li><strong>Draft</strong> — being prepared. <em>A draft budget does not count</em> in the budget-versus-actual and profitability figures.</li>
<li><strong>Approved</strong> — the baseline. Only approved, revised and closed budgets count.</li>
<li><strong>Revised</strong> — changed under approved change control, with a new version number.</li>
<li><strong>Closed</strong> — the project is finished.</li>
</ol>
<blockquote><strong>Common mistake:</strong> leaving the budget from the won quote in Draft. Until it's reviewed and approved, the project shows no budget, and the health score can't rate budget use. Review and approve it at project start.</blockquote>
<h1>Building a realistic budget</h1>
<ul>
<li>Start from the quote's lines, then add what the quote doesn't show: waste allowance, logistics, site overheads, subcontract packages, contingency.</li>
<li>Budget labour by task from estimated hours × rate.</li>
<li>Use past projects' actual costs — the ERP has them.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Recording project costs correctly',
                    'content' => <<<'HTML'
<p><strong>Project Costs</strong> capture everything the project spends. Labour from approved timesheets is created automatically; other costs are recorded on the project's <strong>Costs</strong> tab or under <strong>Project Costs</strong>.</p>
<h1>A project cost record</h1>
<ul>
<li><strong>Project</strong> (required), and where relevant the <strong>component</strong>, <strong>task</strong>, <strong>milestone</strong> and <strong>contractor</strong>.</li>
<li><strong>Cost date</strong> and <strong>cost category</strong> — the same categories as the budget, so they can be compared.</li>
<li><strong>Source</strong> — where the cost came from: manual, purchase order, goods receipt, supplier bill, expense, journal entry, timesheet, stock issue or work order.</li>
<li><strong>Reference number</strong> — the invoice, delivery note or document number, for traceability.</li>
<li><strong>Quantity</strong>, <strong>unit cost</strong> and <strong>amount</strong>, a description and the currency.</li>
</ul>
<h1>Cost statuses</h1>
<ul>
<li><strong>Draft</strong> — recorded but not yet validated. <em>Drafts don't count</em> as actual cost.</li>
<li><strong>Posted</strong> — validated; counts as actual cost in budget-versus-actual and profitability.</li>
<li><strong>Cancelled</strong> — withdrawn, with the history kept.</li>
</ul>
<h1>Getting it right</h1>
<ul>
<li>Record costs promptly — the dashboard is only as current as the costs posted.</li>
<li>Choose the right category and task: a subcontractor's invoice is <em>subcontract</em>, not <em>materials</em>.</li>
<li>Always add a reference so anyone can trace the cost back to its source document.</li>
<li>Never record the same cost twice — for example, don't add labour manually for time that's already on an approved timesheet.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Budget versus actual, earned value and change control',
                    'content' => <<<'HTML'
<h1>Budget versus actual</h1>
<p>The basic control question every week: <em>how much have we spent compared with what we planned — and is that right for the progress we've made?</em> The ERP compares approved budgets with posted costs, by project and by category, showing the remaining budget and the variance.</p>
<h1>Why spend alone isn't enough: earned value</h1>
<p>Having spent 50% of the budget is good news if the job is 60% complete, and bad news if it's 30% complete. <strong>Earned value management</strong>, used on projects worldwide, compares three numbers:</p>
<ul>
<li><strong>Planned value (PV)</strong> — the budgeted cost of the work that <em>should</em> be done by now.</li>
<li><strong>Earned value (EV)</strong> — the budgeted cost of the work that <em>has</em> been done (budget × % complete).</li>
<li><strong>Actual cost (AC)</strong> — what that work actually cost.</li>
</ul>
<p>Then: <strong>cost performance index (CPI) = EV ÷ AC</strong> (below 1.0 means over budget for the work done), and <strong>schedule performance index (SPI) = EV ÷ PV</strong> (below 1.0 means behind schedule).</p>
<blockquote><strong>Example:</strong> budget ₦20m, 40% complete, ₦10m spent. EV = ₦8m, AC = ₦10m, so CPI = 0.8 — every ₦1 spent is producing only ₦0.80 of planned work. Act now, not at the end.</blockquote>
<h1>Burn rate</h1>
<p>The profitability dashboard shows a <strong>burn rate per day</strong> — how fast cost is being spent. If the burn rate says the budget will run out before the planned finish date, you have a problem to solve early.</p>
<h1>Change control (variations)</h1>
<ol>
<li>Record the change request: what, who asked, why.</li>
<li>Assess its effect on cost, time and quality.</li>
<li>Get it approved — by the client for scope changes, and internally for budget changes.</li>
<li>Update the scope, the timeline and the budget (a <strong>revised</strong> budget version), and price it for the client.</li>
</ol>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'A budget was created from the won quote but is still in Draft. What does the profitability dashboard show for budget?', 'options' => [
                    'No budget — only approved, revised and closed budgets count' => true,
                    'The full draft amount' => false,
                    'Double the draft amount' => false,
                    'The quote\'s selling price' => false,
                ]],
                ['q' => 'Which project costs count as actual cost?', 'options' => [
                    'Posted costs' => true,
                    'Draft costs' => false,
                    'Cancelled costs' => false,
                    'Every cost ever typed, in any status' => false,
                ]],
                ['q' => 'A subcontractor\'s invoice for tiling should be recorded in which cost category?', 'options' => [
                    'Subcontract' => true,
                    'Materials' => false,
                    'Overhead' => false,
                    'Other' => false,
                ]],
                ['q' => 'Budget ₦20m, 40% complete, ₦10m actually spent. What is the cost performance index (CPI)?', 'options' => [
                    '0.8 — over budget for the work done' => true,
                    '1.25 — under budget' => false,
                    '2.0' => false,
                    '0.5' => false,
                ]],
                ['q' => 'Select ALL the steps of good change control.', 'options' => [
                    'Record the change request' => true,
                    'Assess its effect on cost, time and quality' => true,
                    'Get it approved and update the budget and timeline' => true,
                    'Absorb it silently' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 7: Billing, profitability and closeout',
            'lessons' => [
                [
                    'title' => 'Project invoices',
                    'content' => <<<'HTML'
<p><strong>Project Invoices</strong> bill the client for project work. Create them on the project's <strong>Invoices</strong> tab or under <strong>Project Invoices</strong>.</p>
<h1>Billing methods</h1>
<ul>
<li><strong>Fixed fee</strong> — an agreed lump sum, or part of it.</li>
<li><strong>Milestone</strong> — stage payments when milestones are reached.</li>
<li><strong>Timesheet</strong> — time and materials, billed from <em>approved, billable</em> timesheets.</li>
<li><strong>Manual</strong> — other agreed commercial lines.</li>
<li><strong>Mixed</strong> — a combination.</li>
</ul>
<p>Each invoice line has a source (milestone, timesheet, fixed fee or manual), a description, quantity, unit price and tax rate, and can be linked to a task or milestone. The invoice has a date, due date, customer, currency (with exchange rate), reference and memo.</p>
<h1>Invoice statuses</h1>
<ol>
<li><strong>Draft</strong> — prepared and checked.</li>
<li><strong>Posted</strong> — issued; counts as billed revenue. Only draft invoices can be posted.</li>
<li><strong>Part paid</strong> / <strong>Paid</strong> — as the client pays.</li>
<li><strong>Voided</strong> — cancelled under control. Only posted or part-paid invoices can be voided, and the void is recorded.</li>
</ol>
<h1>Good practice</h1>
<ul>
<li>Invoice as soon as a milestone is reached or the billing period ends. Late invoices are late cash.</li>
<li>Before posting, check quantities, prices, tax and the contract terms (retention, advance recovery). Post only after commercial review.</li>
<li>Attach the evidence the client needs: completion photos, signed delivery notes, approved valuation.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'The profitability dashboard and project health',
                    'content' => <<<'HTML'
<p>The <strong>Project Profitability</strong> dashboard brings the numbers together for one project, over a date range you choose.</p>
<h1>What it shows</h1>
<ul>
<li><strong>Budget</strong>, <strong>actual cost</strong>, <strong>remaining budget</strong>, <strong>budget used %</strong> and <strong>variance</strong>.</li>
<li><strong>Revenue basis</strong> and <strong>billed revenue</strong> (posted, part-paid and paid invoices).</li>
<li><strong>Gross profit</strong> and <strong>gross margin %</strong>.</li>
<li><strong>Labour cost</strong>, <strong>non-labour cost</strong> and the <strong>labour ratio</strong>.</li>
<li><strong>Total hours</strong>, <strong>billable hours</strong> and the billable ratio.</li>
<li><strong>Burn rate per day</strong>.</li>
<li><strong>Task completion</strong> and <strong>milestone completion</strong>.</li>
<li><strong>Top cost categories</strong>.</li>
</ul>
<h1>The health score</h1>
<p>The dashboard rates each project out of 100:</p>
<ul>
<li><strong>Budget use (up to 35 points):</strong> 35 if 70% or less of the budget is used, 25 up to 90%, 15 up to 100%, and 0 if over budget — or if there's no approved budget.</li>
<li><strong>Gross margin (up to 30 points):</strong> 30 at 30% or more, 20 at 15% or more, 10 at break-even or better, 0 if loss-making.</li>
<li><strong>Progress (up to 35 points):</strong> the average of task and milestone completion — 35 at 80% or more, 25 at 50%, 15 at 25%, otherwise 5.</li>
</ul>
<p>A score of <strong>75 or more is Healthy</strong>, <strong>45 to 74 is Watch</strong>, and <strong>below 45 is Critical</strong>.</p>
<blockquote><strong>Read the score with judgement.</strong> A project early in its life has low progress but should have low spend too. The score is a prompt for questions — the answers come from the details.</blockquote>
HTML,
                ],
                [
                    'title' => 'Closing out a project',
                    'content' => <<<'HTML'
<p>Many firms lose money at the end of a job: snags that drag on, final invoices never raised, retention never collected, and the same mistakes repeated on the next project. Closing out properly prevents all of these.</p>
<h1>The closeout checklist</h1>
<ol>
<li><strong>Practical completion</strong> — all work done, snags closed, client sign-off recorded and its milestone completed.</li>
<li><strong>Handover pack</strong> — as-built drawings, product data, care and maintenance guides, warranties and certificates, uploaded to the project's Documents.</li>
<li><strong>Final account</strong> — all variations priced and agreed; final invoice raised and posted.</li>
<li><strong>Costs complete</strong> — every supplier and subcontractor cost posted, every timesheet approved, no drafts left.</li>
<li><strong>Tasks tidy</strong> — every task completed or cancelled; nothing left pending or in progress.</li>
<li><strong>Retention and aftercare</strong> — retention release date recorded and followed up; the defects period managed.</li>
<li><strong>Budget closed</strong> and the project status set to <strong>Completed</strong>.</li>
<li><strong>Lessons learned</strong> — a short review: what went well, what didn't, actual versus estimated hours and costs. Feed it into the next estimate.</li>
</ol>
<p>You've reached the end of the lessons. Mark this one complete, then take the final assessment: 25 questions in 40 minutes. You need 80% to pass, with up to 3 attempts.</p>
HTML,
                ],
            ],
            'final' => [
                'title' => 'Projects, Site Installation & Construction — Final Assessment',
                'slug' => 'erp-projects-assessment',
                'description' => '<p>The final assessment for Projects, Site Installation &amp; Construction in Thekan ERP. It covers project-delivery practice, site safety and quality, and running projects in the ERP.</p>',
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
                    ['q' => 'Which stage of the project lifecycle compares progress and cost with the plan every week and acts on the differences?', 'options' => [
                        'Monitor and control' => true,
                        'Initiate' => false,
                        'Close' => false,
                        'Procure' => false,
                    ]],
                    ['q' => 'What is a project baseline?', 'options' => [
                        'The approved plan and budget that progress and cost are measured against' => true,
                        'The ground floor of the building' => false,
                        'The first invoice' => false,
                        'The cheapest supplier quote' => false,
                    ]],
                    ['q' => 'A hotel fit-out is split into "Floor 3 — Bedrooms 301–320", then shop drawings, manufacture, delivery, installation and snagging. What is this?', 'options' => [
                        'A work breakdown structure' => true,
                        'A cost category' => false,
                        'A payment certificate' => false,
                        'A risk assessment' => false,
                    ]],
                    ['q' => 'Client approval of shop drawings is late and manufacture can\'t start. Which part of the schedule is most likely affected?', 'options' => [
                        'The critical path — and therefore the finish date' => true,
                        'Nothing, because approvals have float' => false,
                        'Only the invoice dates' => false,
                        'The site welfare plan' => false,
                    ]],
                    ['q' => 'Select ALL the features of a good milestone.', 'options' => [
                        'Objectively measurable' => true,
                        'One owner and a target date' => true,
                        'Aligned with the contract\'s payment terms where relevant' => true,
                        'Vague, so it is always reached' => false,
                    ]],
                    ['q' => 'What is the leading cause of serious injury in construction?', 'options' => [
                        'Working at height' => true,
                        'Using the ERP' => false,
                        'Toolbox talks' => false,
                        'Wearing PPE' => false,
                    ]],
                    ['q' => 'An installer believes a mobile tower is unsafe. What should they do?', 'options' => [
                        'Stop the work — anyone may stop unsafe work in good faith' => true,
                        'Carry on to stay on programme' => false,
                        'Wait for the weekly meeting' => false,
                        'Ask the client for permission to continue' => false,
                    ]],
                    ['q' => 'In Thekan ERP, what works like a hold point on a task?', 'options' => [
                        'A required checklist — the task can\'t be submitted until it passes' => true,
                        'A chat group' => false,
                        'The project code' => false,
                        'The task QR code' => false,
                    ]],
                    ['q' => 'Select ALL the good furniture-installation practices.', 'options' => [
                        'Protect floors and walls before starting' => true,
                        'Anchor tall units against tipping' => true,
                        'Store items in installation order in the area where they\'ll be fitted' => true,
                        'Leave packaging in the room until handover' => false,
                    ]],
                    ['q' => 'When does the ERP let you create a project from a sales quote?', 'options' => [
                        'When the quote is won, and only once per quote' => true,
                        'Whenever a quote is drafted' => false,
                        'Only after the final invoice is paid' => false,
                        'Never — projects must be typed in' => false,
                    ]],
                    ['q' => 'A project is paused while the client decides on finishes. Which status fits?', 'options' => [
                        'On hold' => true,
                        'Completed' => false,
                        'Cancelled' => false,
                        'Draft' => false,
                    ]],
                    ['q' => 'You move a milestone on the timeline. What happens to the tasks?', 'options' => [
                        'Nothing — moving a milestone changes only its own target date' => true,
                        'All tasks move with it' => false,
                        'All tasks are cancelled' => false,
                        'The project is completed' => false,
                    ]],
                    ['q' => 'You try to make Task A depend on Task B, but B already depends on A. What does the ERP do?', 'options' => [
                        'Refuses it, because it would create a circular reference' => true,
                        'Accepts it and loops forever' => false,
                        'Deletes both tasks' => false,
                        'Completes both tasks' => false,
                    ]],
                    ['q' => 'Why should "Confirmation required" stay on for site installation tasks?', 'options' => [
                        'So a supervisor reviews and signs off the work before it counts as complete' => true,
                        'So the task can never be completed' => false,
                        'So the client is invoiced automatically' => false,
                        'It has no effect' => false,
                    ]],
                    ['q' => 'An installer\'s timesheet for 8 hours at ₦3,000 per hour is approved. What does the ERP record?', 'options' => [
                        'A posted labour cost of ₦24,000 on the project' => true,
                        'Nothing until someone adds the cost manually' => false,
                        'A sales invoice for ₦24,000' => false,
                        'A draft cost that must be posted later' => false,
                    ]],
                    ['q' => 'Labour for a task is on an approved timesheet. Should you also add a manual labour cost for the same time?', 'options' => [
                        'No — that would record the cost twice' => true,
                        'Yes, to be safe' => false,
                        'Yes, but in the Materials category' => false,
                        'Only at the end of the project' => false,
                    ]],
                    ['q' => 'Where can you see every photo attached to a project and its tasks in one place?', 'options' => [
                        'The project\'s Gallery tab' => true,
                        'The Chat page' => false,
                        'The payroll run' => false,
                        'The supplier list' => false,
                    ]],
                    ['q' => 'Select ALL the project cost categories in Thekan ERP.', 'options' => [
                        'Materials' => true,
                        'Labour' => true,
                        'Subcontract' => true,
                        'Gossip' => false,
                    ]],
                    ['q' => 'What should every manually recorded project cost include, so it can be traced?', 'options' => [
                        'A reference number (invoice, delivery note or document)' => true,
                        'The supplier\'s password' => false,
                        'A chat link' => false,
                        'Nothing else' => false,
                    ]],
                    ['q' => 'A client asks for a change that adds cost. After it\'s approved, what should happen to the project budget?', 'options' => [
                        'It is revised under change control, with a new version' => true,
                        'It stays the same and the overspend is ignored' => false,
                        'It is deleted' => false,
                        'It is put back to Draft forever' => false,
                    ]],
                    ['q' => 'Which project invoices can be voided?', 'options' => [
                        'Posted or part-paid invoices' => true,
                        'Only draft invoices' => false,
                        'Any invoice, by deleting it' => false,
                        'None — invoices can never be corrected' => false,
                    ]],
                    ['q' => 'A time-and-materials job should be billed from which source?', 'options' => [
                        'Approved, billable timesheets' => true,
                        'Draft timesheets' => false,
                        'Rejected timesheets' => false,
                        'Chat messages' => false,
                    ]],
                    ['q' => 'A project has used 95% of its approved budget, earns a 20% gross margin and is 60% complete on average. What health rating does it get?', 'options' => [
                        'Watch (15 + 20 + 25 = 60 points)' => true,
                        'Healthy' => false,
                        'Critical' => false,
                        'Not rated' => false,
                    ]],
                    ['q' => 'Select ALL the things that must be done before a project is set to Completed.', 'options' => [
                        'Snags closed and client sign-off recorded' => true,
                        'Final invoice posted and all costs posted' => true,
                        'All tasks completed or cancelled' => true,
                        'All documents deleted' => false,
                    ]],
                    ['q' => 'Why hold a lessons-learned review at closeout?', 'options' => [
                        'To feed actual hours and costs, and what went well or badly, into the next estimate and plan' => true,
                        'To find someone to blame' => false,
                        'It is not useful' => false,
                        'Because the client requires a party' => false,
                    ]],
                ],
            ],
        ],
    ],

    // Recommended to the project and site delivery team.
    'recommended_for' => [
        'Project Manager', 'Site Supervisor', 'Site Engineer', 'Installation Supervisor',
        'Quantity Surveyor', 'Architect',
    ],
];
