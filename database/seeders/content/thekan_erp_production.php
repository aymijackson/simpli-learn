<?php

/*
 * "Furniture Production in Thekan ERP" — content for ThekanServicesSeeder.
 *
 * Global manufacturing practice applied to a bespoke furniture workshop,
 * and the Production module feature by feature: work centres, routings,
 * cost types, bills of materials (and BOM deficits), work orders, material
 * issue and return, routing steps, cost lines, completion and analytics.
 *
 * Statuses, rules and costing match Thekan ERP as built.
 *
 * Quiz format: 'q' => question, 'options' => [text => is_correct, ...].
 * More than one correct option makes it a "select all that apply" question.
 */

return [
    'test_prefix' => 'erp-production',
    'test_label' => 'Production',

    'course' => [
        'title' => 'Furniture Production in Thekan ERP',
        'slug' => 'thekan-erp-production',
        'description' => <<<'HTML'
<p>Thekan's workshop turns drawings into kitchens, wardrobes, joinery and furniture that our clients live and work with every day. A well-run workshop delivers on time, to the drawing, at the cost we estimated — and knows exactly what each job really cost.</p>
<p>This course teaches global manufacturing practice applied to a bespoke furniture workshop, and the <strong>Production</strong> module of Thekan ERP feature by feature: work centres, routings and cost types, bills of materials, work orders, issuing and returning materials, routing steps, cost lines, completion with good and scrap quantities, and production analytics.</p>
<ul>
<li><strong>Who it's for:</strong> production supervisors, workshop leads, storekeepers who supply the workshop, and planners.</li>
<li><strong>Before you start:</strong> complete <em>Thekan ERP Foundations</em>. This course opens once you've passed it.</li>
<li><strong>How it works:</strong> 6 modules, each ending with a test you must pass to unlock the next. The last module ends with the final assessment.</li>
<li><strong>To complete it:</strong> finish every lesson and score at least 80% on the final assessment. You'll receive a certificate.</li>
</ul>
HTML,
        'catalog' => [
            'subtitle' => 'Make it right, make it once, and know what it cost — from BOM to finished furniture.',
            'category' => 'Thekan ERP',
            'level' => 'intermediate',
            'duration_minutes' => 180,
            'instructor_name' => 'Thekan Services Limited',
            'instructor_bio' => 'Prepared by Thekan for its workshop and production teams, based on recognised manufacturing practice and on how Thekan ERP is built.',
            'outcomes' => [
                'Apply lean, quality-at-source and workshop safety practice to furniture production',
                'Set up work centres, routings and cost types that reflect how the workshop really works',
                'Build and approve bills of materials, and control changes to them',
                'Run work orders from draft to closed: release, start, pause, complete and close',
                'Issue and return materials, finish routing steps and record extra costs',
                'Read yield, scrap and cost — and use them to improve the next job',
            ],
        ],
    ],

    'modules' => [

        // ------------------------------------------------------------------
        [
            'title' => 'Module 1: Workshop excellence — global best practice',
            'lessons' => [
                [
                    'title' => 'How a bespoke furniture workshop should run',
                    'preview' => true,
                    'content' => <<<'HTML'
<p>Thekan's workshop mostly <strong>makes to order</strong>: each job is designed for a client, then cut, edged, machined, assembled, finished and packed. That's different from a factory making thousands of identical chairs — but the principles that make the world's best manufacturers efficient still apply.</p>
<h1>Lean manufacturing: remove waste</h1>
<p>Lean thinking, which grew out of Toyota, names the wastes that cost time and money without adding value:</p>
<ul>
<li><strong>Defects</strong> — recutting a panel because the drawing was misread.</li>
<li><strong>Waiting</strong> — the edgebander idle because cut panels haven't arrived.</li>
<li><strong>Over-production</strong> — making extra "just in case".</li>
<li><strong>Excess stock and work in progress</strong> — half-finished cabinets filling the floor.</li>
<li><strong>Unnecessary movement and transport</strong> — walking to the store five times for fittings.</li>
<li><strong>Over-processing</strong> — finishing surfaces nobody will ever see.</li>
<li><strong>Unused talent</strong> — not asking the people doing the work how to do it better.</li>
</ul>
<h1>Make it right first time</h1>
<ul>
<li><strong>Approved drawings only</strong> — never cut from a drawing that isn't the approved, current version.</li>
<li><strong>Cutting lists checked</strong> before the first cut.</li>
<li><strong>First-off inspection</strong> — check the first unit thoroughly before making the rest.</li>
<li><strong>Quality at source</strong> — each station checks its own work, rather than relying on a final inspection to catch everything.</li>
</ul>
<blockquote>The cheapest place to find a mistake is on the drawing. The most expensive is in the client's house.</blockquote>
HTML,
                ],
                [
                    'title' => 'Workshop safety and planning',
                    'content' => <<<'HTML'
<h1>Safety first</h1>
<ul>
<li><strong>Machine guarding</strong> — guards on saws, routers and spindles stay on. Push sticks for narrow cuts.</li>
<li><strong>Training and authorisation</strong> — only trained, authorised people operate each machine.</li>
<li><strong>Dust extraction</strong> — wood and MDF dust is a health and fire hazard. Extraction on every machine; masks when sanding.</li>
<li><strong>PPE</strong> — eye and ear protection, safety boots, gloves where appropriate (never near rotating blades).</li>
<li><strong>Finishing areas</strong> — ventilated spray booths, solvents stored safely, no ignition sources.</li>
<li><strong>Lock off</strong> machines before changing blades or clearing jams.</li>
<li><strong>Housekeeping</strong> — offcuts and dust cleared; walkways clear.</li>
</ul>
<h1>Planning the workshop</h1>
<ul>
<li>Plan from the <strong>project programme</strong>: work back from installation dates to when each job must be cut, assembled and finished.</li>
<li>Know each station's <strong>capacity</strong>. The slowest station (often finishing or the edgebander) sets the pace for everything — schedule around it.</li>
<li>Confirm <strong>materials are available</strong> before releasing a job to the floor. A job started without its hinges becomes work in progress that blocks the floor.</li>
<li>Batch similar work where it helps (same board, same colour), without delaying urgent jobs.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Recutting a panel because a drawing was misread is which lean waste?', 'options' => [
                    'Defects' => true,
                    'Transport' => false,
                    'Over-production' => false,
                    'Unused talent' => false,
                ]],
                ['q' => 'What is a first-off inspection?', 'options' => [
                    'Checking the first unit thoroughly before making the rest' => true,
                    'Inspecting the last unit only' => false,
                    'Switching machines off first' => false,
                    'The first inspection of the year' => false,
                ]],
                ['q' => 'What sets the pace of the whole workshop?', 'options' => [
                    'The slowest station (the bottleneck)' => true,
                    'The fastest machine' => false,
                    'The store' => false,
                    'The office printer' => false,
                ]],
                ['q' => 'Why confirm materials are available before releasing a job?', 'options' => [
                    'A job started without its materials becomes work in progress blocking the floor' => true,
                    'Because the ERP is slow' => false,
                    'To delay the job' => false,
                    'It doesn\'t matter' => false,
                ]],
                ['q' => 'Select ALL the workshop safety essentials.', 'options' => [
                    'Guards stay on machines' => true,
                    'Dust extraction on every machine' => true,
                    'Lock off machines before changing blades' => true,
                    'Gloves near rotating blades' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 2: Production set-up',
            'lessons' => [
                [
                    'title' => 'Work centres and cost types',
                    'content' => <<<'HTML'
<h1>Work centres</h1>
<p>A <strong>work centre</strong> (Production Parameters → Work Centers) is a place or machine group where work is done. For Thekan's workshop, typically: <em>Panel saw / CNC</em>, <em>Edgebanding</em>, <em>Machining &amp; drilling</em>, <em>Assembly</em>, <em>Spray finishing</em>, <em>Upholstery</em>, <em>Quality check &amp; packing</em>.</p>
<ul>
<li>Each has a <strong>name</strong>, a description, a <strong>default labour rate</strong> and a <strong>default machine rate</strong>, and can be made inactive.</li>
<li>Realistic rates make work order costs realistic. Review them when wages, power costs or machines change.</li>
</ul>
<h1>Work order cost types</h1>
<p><strong>WO Cost Types</strong> are the kinds of extra cost recorded against work orders besides materials: each has a unique <strong>code</strong>, a <strong>name</strong>, a <strong>category</strong> — <strong>labour</strong>, <strong>machine</strong>, <strong>logistics</strong>, <strong>fuel</strong>, <strong>service</strong>, <strong>overhead</strong> or <strong>other</strong> — and a default unit.</p>
<p>Examples: <em>LAB-ASSY</em> (labour, hours), <em>CNC-RUN</em> (machine, hours), <em>SPRAY-SUB</em> (service, item — outsourced spraying), <em>GEN-FUEL</em> (fuel, litres).</p>
HTML,
                ],
                [
                    'title' => 'Routings and routing steps',
                    'content' => <<<'HTML'
<p>A <strong>routing</strong> is the sequence of operations to make a product. Create routings under <strong>Production Parameters → Routings</strong>, for a product variant, with a name and description.</p>
<h1>Routing steps</h1>
<p>Each <strong>step</strong> has a <strong>name</strong>, <strong>instructions</strong> and a <strong>sequence</strong> number. Steps can be re-ordered. A typical cabinet routing:</p>
<ol>
<li>Cut panels to cutting list (panel saw / CNC).</li>
<li>Edgeband visible edges.</li>
<li>Drill and machine for hinges, runners and connectors.</li>
<li>Assemble carcass; fit back panel.</li>
<li>Fit doors, drawers and hardware; adjust.</li>
<li>Quality check against drawing; clean.</li>
<li>Protect, label and pack.</li>
</ol>
<h1>Good routings</h1>
<ul>
<li>Write instructions a new team member could follow — including the checks at each step.</li>
<li>Include the quality check and packing steps; they take real time.</li>
<li>Use <strong>Duplicate</strong> to start a similar routing rather than retyping.</li>
</ul>
<p>When a work order is released, its routing's steps are copied onto the work order, so each job's progress can be tracked step by step.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What does a work centre\'s default labour and machine rate affect?', 'options' => [
                    'How realistic work order costs are' => true,
                    'The client\'s VAT' => false,
                    'The store\'s location' => false,
                    'Nothing' => false,
                ]],
                ['q' => 'Outsourced spray finishing belongs to which cost type category?', 'options' => [
                    'Service' => true,
                    'Fuel' => false,
                    'Machine' => false,
                    'Logistics' => false,
                ]],
                ['q' => 'What is a routing?', 'options' => [
                    'The sequence of operations to make a product' => true,
                    'A delivery route' => false,
                    'A list of materials' => false,
                    'A sales quote' => false,
                ]],
                ['q' => 'What happens to routing steps when a work order is released?', 'options' => [
                    'They are copied onto the work order so progress can be tracked step by step' => true,
                    'They are deleted' => false,
                    'They are emailed to the client' => false,
                    'Nothing' => false,
                ]],
                ['q' => 'Select ALL the work order cost type categories.', 'options' => [
                    'Labour and machine' => true,
                    'Logistics and fuel' => true,
                    'Service and overhead' => true,
                    'Gifts' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 3: Bills of materials',
            'lessons' => [
                [
                    'title' => 'Building a BOM',
                    'content' => <<<'HTML'
<p>A <strong>bill of materials (BOM)</strong> is the recipe: every component and how much of it goes into one unit of the finished product. It drives what the store issues to the workshop and what the job costs.</p>
<h1>The BOM header</h1>
<ul>
<li>The <strong>product variant</strong> being made — for example "Wardrobe 2-door, 1200×600×2400, oak veneer".</li>
<li>A unique <strong>name</strong> and unique <strong>BOM code</strong>.</li>
<li>A description and the <strong>yield quantity</strong> — how many units the recipe makes (usually 1).</li>
</ul>
<h1>BOM items</h1>
<p>Each item is a component variant and its <strong>quantity per parent</strong>: sheets of board, metres of edging, number of hinges, runners, handles, screws, litres of lacquer.</p>
<h1>Getting quantities right</h1>
<ul>
<li>Base sheet quantities on the cutting optimisation, including realistic offcut waste.</li>
<li>Don't forget the small items — screws, dowels, cam fittings, glue, edging. They add up, and a missing box of connectors stops assembly.</li>
<li>Check the unit of each item (sheet, metre, piece, litre).</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Approving and changing BOMs',
                    'content' => <<<'HTML'
<h1>Draft and approved</h1>
<ul>
<li>A new BOM is a draft and can be edited.</li>
<li>When it's checked, <strong>approve</strong> it. An approved BOM's recipe is <strong>locked</strong>, because work orders may already be using it.</li>
<li>An approved BOM can't be deleted.</li>
</ul>
<h1>Changing an approved BOM</h1>
<p>If the design or materials change, <strong>duplicate</strong> the BOM, edit the copy and approve it as a new BOM. Work orders already running keep the recipe they were released with; new work orders use the new BOM. That's standard engineering change control — you can always tell which recipe a job was made to.</p>
<h1>BOM deficits</h1>
<p>Sometimes components allocated to one BOM's production are lent to another to keep the workshop moving. Thekan ERP records this through <strong>BOM Deficits</strong>: the borrowing BOM carries an outstanding deficit for those components.</p>
<ul>
<li>Deficits are repaid when stock is next issued to the borrowing BOM, or recorded with a <strong>repay</strong>, <strong>write-off</strong> or <strong>adjust</strong> transaction and a note.</li>
<li>Quantities must be greater than zero and can't exceed what's outstanding.</li>
<li><strong>BOM Deficit Txns</strong> shows the history. Keep deficits small and short-lived — they're a sign materials weren't planned in time.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What is a bill of materials?', 'options' => [
                    'The recipe of components and quantities for one unit of a product' => true,
                    'A supplier\'s invoice' => false,
                    'A sales quote' => false,
                    'A list of workshop staff' => false,
                ]],
                ['q' => 'The design of an approved wardrobe BOM changes. What should you do?', 'options' => [
                    'Duplicate it, edit the copy and approve it as a new BOM' => true,
                    'Edit the approved BOM directly' => false,
                    'Delete the approved BOM' => false,
                    'Keep using the old BOM and fix it on the floor' => false,
                ]],
                ['q' => 'Why is an approved BOM locked?', 'options' => [
                    'Work orders may already be using it' => true,
                    'To stop it being printed' => false,
                    'Because the supplier owns it' => false,
                    'It isn\'t locked' => false,
                ]],
                ['q' => 'What is "quantity per parent"?', 'options' => [
                    'How much of a component goes into one unit of the finished product' => true,
                    'The number of parents on the team' => false,
                    'The price per unit' => false,
                    'The stock level' => false,
                ]],
                ['q' => 'Select ALL the ways a BOM deficit can be recorded or cleared.', 'options' => [
                    'Repay' => true,
                    'Write-off' => true,
                    'Adjust' => true,
                    'Ignore forever' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 4: Work orders',
            'lessons' => [
                [
                    'title' => 'Creating a work order',
                    'content' => <<<'HTML'
<p>A <strong>work order (WO)</strong> is the instruction to make a quantity of a product. Create it under <strong>Production → Work Orders</strong>.</p>
<ul>
<li><strong>Work order number</strong>.</li>
<li><strong>Product variant</strong> to make, its approved <strong>BOM</strong> and its <strong>routing</strong>.</li>
<li><strong>Store</strong> — where materials are issued from and finished goods are received into.</li>
<li><strong>Quantity to produce</strong>, start and end dates.</li>
<li>The <strong>project</strong> and <strong>project component</strong> it's for — so the work order's cost flows into the project's costs.</li>
</ul>
<p>Use <strong>Duplicate</strong> for repeat work. Only draft work orders can be edited or deleted.</p>
<h1>Before you release — check</h1>
<ul>
<li>The drawings are approved and current.</li>
<li>The BOM and routing are the right ones for this design.</li>
<li>Materials are in stock (or arriving in time).</li>
<li>The store is set.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'The work order lifecycle',
                    'content' => <<<'HTML'
<ol>
<li><strong>Draft</strong> — being set up.</li>
<li><strong>Released</strong> — select <strong>Release</strong>. Only drafts can be released, and a store must be set. Releasing copies the BOM into the work order's <strong>materials</strong> list and the routing into its <strong>steps</strong>, so later changes to the BOM or routing don't change this job.</li>
<li><strong>In progress</strong> — select <strong>Start</strong> (from released or paused).</li>
<li><strong>Paused</strong> — only an in-progress work order can be paused, for example while waiting for materials or a client decision.</li>
<li><strong>Completed</strong> — see the next module.</li>
<li><strong>Closed</strong> — only completed work orders can be closed, once everything is checked.</li>
</ol>
<h1>Cancelling</h1>
<ul>
<li>Completed, closed or already-cancelled work orders can't be cancelled.</li>
<li>All issued materials must be <strong>returned</strong> to the store first — so stock and costs stay correct.</li>
</ul>
<p>Assignees and supervisors of work order tasks see them on their dashboard under <strong>Production</strong>, and work order events notify the people concerned.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What does releasing a work order do?', 'options' => [
                    'Copies the BOM into its materials and the routing into its steps' => true,
                    'Completes it' => false,
                    'Invoices the client' => false,
                    'Deletes the BOM' => false,
                ]],
                ['q' => 'What must be set before a work order can be released?', 'options' => [
                    'A store' => true,
                    'A sales invoice' => false,
                    'A payment' => false,
                    'Nothing' => false,
                ]],
                ['q' => 'Which work orders can be paused?', 'options' => [
                    'In-progress work orders' => true,
                    'Draft work orders' => false,
                    'Closed work orders' => false,
                    'Any work order' => false,
                ]],
                ['q' => 'A work order with issued materials must be cancelled. What must happen first?', 'options' => [
                    'Return all issued materials to the store' => true,
                    'Delete the BOM' => false,
                    'Complete it' => false,
                    'Nothing' => false,
                ]],
                ['q' => 'Why link a work order to a project and component?', 'options' => [
                    'So its cost flows into the project\'s costs' => true,
                    'To hide it from the workshop' => false,
                    'It changes the BOM' => false,
                    'It isn\'t possible' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 5: Running and completing the job',
            'lessons' => [
                [
                    'title' => 'Materials, steps and extra costs',
                    'content' => <<<'HTML'
<h1>Materials</h1>
<p>The work order's <strong>materials</strong> list starts from the BOM, multiplied by the quantity. You can adjust planned quantities or add lines with a note.</p>
<ul>
<li><strong>Issue</strong> materials as the job needs them. You can't issue more than the remaining planned quantity — if more is genuinely needed (a recut), increase the plan with a note first, so the overrun is visible.</li>
<li><strong>Return</strong> unused materials to the store; you can't return more than was issued.</li>
<li>A material line with any issue or return activity can't be deleted.</li>
<li>Each issue is costed at the item's unit cost when first issued, and builds up the job's material cost.</li>
</ul>
<h1>Steps</h1>
<p>Work through the routing <strong>steps</strong>: <strong>Start</strong> a pending step, then <strong>Finish</strong> it when done. This shows exactly where every job is on the floor.</p>
<h1>Tasks</h1>
<p>Work orders can also carry <strong>tasks</strong> (from the shared Tasks system) — with assignees, supervisors, checklists for quality checks, blockers and time logs.</p>
<h1>Cost lines</h1>
<p>Record extra costs as <strong>cost lines</strong>: a cost type, a quantity, a rate and a note — for example 14 hours of assembly labour at the labour rate, or outsourced spraying as one service item. Each line costs quantity × rate.</p>
HTML,
                ],
                [
                    'title' => 'Completing a work order',
                    'content' => <<<'HTML'
<p>When the job is made, select <strong>Complete</strong>. The work order must be <strong>in progress</strong>, must have a store, and <strong>every routing step must be finished</strong>.</p>
<h1>Good and scrap quantities</h1>
<p>Enter the <strong>good quantity</strong> (finished and acceptable) and the <strong>scrap quantity</strong> (spoiled). If you leave them blank, the ERP assumes the full planned quantity was good and nothing was scrapped — so record real counts.</p>
<h1>What completion calculates and records</h1>
<ul>
<li><strong>Material cost</strong> = (issued − returned) × unit cost, for every material line.</li>
<li><strong>Extra cost</strong> = the total of the cost lines.</li>
<li><strong>Total cost</strong> = material cost + extra cost.</li>
<li><strong>Unit cost of finished goods</strong> = total cost ÷ good quantity. Scrap raises the cost of every good unit.</li>
<li><strong>Yield</strong> = good ÷ (good + scrap).</li>
<li>The good quantity is <strong>received into the store</strong> as finished goods, and the accounting entries are posted.</li>
<li>If the work order is linked to a project, its material and extra costs are recorded in the project's costs.</li>
</ul>
<blockquote><strong>Example:</strong> materials ₦600,000, cost lines ₦240,000, 10 good and 2 scrapped. Total ₦840,000; unit cost ₦84,000; yield 10 ÷ 12 ≈ 83%. With no scrap from the same spend, it would be 12 units at ₦70,000.</blockquote>
<p>Then check everything — returns done, costs complete — and <strong>close</strong> the work order.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'You need more board than planned because of a recut. What is the right approach?', 'options' => [
                    'Increase the planned quantity with a note, then issue — so the overrun is visible' => true,
                    'Take it from the store without recording it' => false,
                    'Issue it on another work order' => false,
                    'Make a stock adjustment' => false,
                ]],
                ['q' => 'Why might Complete be refused?', 'options' => [
                    'Not all routing steps are finished' => true,
                    'The work order has a project' => false,
                    'Scrap was recorded' => false,
                    'The store is set' => false,
                ]],
                ['q' => 'Total cost ₦500,000, good quantity 5, scrap 0. What is the finished-goods unit cost?', 'options' => [
                    '₦100,000' => true,
                    '₦500,000' => false,
                    '₦50,000' => false,
                    '₦125,000' => false,
                ]],
                ['q' => 'What happens if good and scrap quantities are left blank on completion?', 'options' => [
                    'The ERP assumes the full planned quantity was good and none scrapped' => true,
                    'Completion is refused' => false,
                    'Everything is scrapped' => false,
                    'The work order is cancelled' => false,
                ]],
                ['q' => 'Select ALL the things completion does.', 'options' => [
                    'Calculates material, extra and total cost' => true,
                    'Receives the good quantity into the store' => true,
                    'Records the costs on the linked project' => true,
                    'Sends the client an invoice' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 6: Analytics and continuous improvement',
            'lessons' => [
                [
                    'title' => 'Reading production performance',
                    'content' => <<<'HTML'
<p><strong>Production Analytics</strong> shows how the workshop is performing across work orders. The measures that matter most:</p>
<ul>
<li><strong>On-time completion</strong> — work orders completed by their end date. Late workshop jobs become late installations.</li>
<li><strong>Yield and scrap</strong> — good ÷ (good + scrap). Every scrapped panel is material, machine time and labour lost.</li>
<li><strong>Actual versus planned material</strong> — issued against planned. Consistent overruns mean the BOM or the cutting is wrong.</li>
<li><strong>Cost per unit</strong> — compared with what was estimated on the quote.</li>
<li><strong>Work in progress</strong> — open and paused work orders. Too many means jobs are started before they can be finished.</li>
</ul>
<h1>Feeding the estimate</h1>
<p>Completed work order costs flow into project costs, so the project's actual cost can be compared with what was quoted. Share differences with the estimators: if wardrobes consistently cost 15% more to make than we price them, the catalogue rates need to change.</p>
HTML,
                ],
                [
                    'title' => 'Continuous improvement in the workshop',
                    'content' => <<<'HTML'
<p>The best manufacturers improve a little every week — the Japanese word <strong>kaizen</strong>.</p>
<h1>A simple routine</h1>
<ol>
<li><strong>Weekly review:</strong> late work orders, scrap, material overruns, blockers.</li>
<li><strong>Ask why five times</strong> for each big problem until you reach the root cause. "The panel was cut wrong" → "the cutting list was wrong" → "the drawing changed after the list was printed" → "drawing revisions aren't communicated to the workshop" → fix the revision process.</li>
<li><strong>Fix the process</strong>, not just the panel: update the BOM, the routing instructions, a checklist or a template.</li>
<li><strong>Involve the team</strong> — the people at the machines usually know where the waste is.</li>
</ol>
<h1>Standard work</h1>
<p>When a better way is found, write it into the routing step instructions and task checklists, so everyone does it the same way and new staff learn it from day one.</p>
<p>Mark this lesson complete, then take the final assessment: 20 questions in 30 minutes. You need 80% to pass, with up to 3 attempts.</p>
HTML,
                ],
            ],
            'final' => [
                'title' => 'Furniture Production — Final Assessment',
                'slug' => 'erp-production-assessment',
                'description' => '<p>The final assessment for Furniture Production in Thekan ERP, covering workshop practice, BOMs, routings and work orders.</p>',
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
                    ['q' => 'Half-finished cabinets filling the workshop floor are an example of which waste?', 'options' => [
                        'Excess work in progress' => true,
                        'Unused talent' => false,
                        'Defects' => false,
                        'Over-processing' => false,
                    ]],
                    ['q' => 'Which drawing may the workshop cut from?', 'options' => [
                        'Only the approved, current version' => true,
                        'Any version on the wall' => false,
                        'The first draft' => false,
                        'A photo sent by chat' => false,
                    ]],
                    ['q' => 'Where is the cheapest place to find a mistake?', 'options' => [
                        'On the drawing' => true,
                        'In the client\'s house' => false,
                        'During installation' => false,
                        'At handover' => false,
                    ]],
                    ['q' => 'Select ALL the good workshop planning practices.', 'options' => [
                        'Work back from installation dates' => true,
                        'Schedule around the bottleneck station' => true,
                        'Confirm materials before releasing a job' => true,
                        'Start every job at once' => false,
                    ]],
                    ['q' => 'What does a work centre record?', 'options' => [
                        'A name, description and default labour and machine rates' => true,
                        'Customer contacts' => false,
                        'Sales prices' => false,
                        'Bank accounts' => false,
                    ]],
                    ['q' => 'What does each routing step have?', 'options' => [
                        'A name, instructions and a sequence' => true,
                        'A price and a customer' => false,
                        'A supplier and a PO' => false,
                        'Nothing but a number' => false,
                    ]],
                    ['q' => 'Which fields must be unique on a BOM?', 'options' => [
                        'The name and the BOM code' => true,
                        'The description' => false,
                        'The yield quantity' => false,
                        'Nothing' => false,
                    ]],
                    ['q' => 'Can an approved BOM be deleted?', 'options' => [
                        'No — it may be referenced by work orders' => true,
                        'Yes, always' => false,
                        'Only on Fridays' => false,
                        'Only by the client' => false,
                    ]],
                    ['q' => 'A BOM is changed by approving a new copy. What happens to work orders already released on the old BOM?', 'options' => [
                        'They keep the materials they were released with' => true,
                        'They switch to the new BOM automatically' => false,
                        'They are cancelled' => false,
                        'They are deleted' => false,
                    ]],
                    ['q' => 'What do BOM deficits usually indicate?', 'options' => [
                        'Materials weren\'t planned in time and were borrowed between BOMs' => true,
                        'A perfect plan' => false,
                        'A sales discount' => false,
                        'A payroll error' => false,
                    ]],
                    ['q' => 'Which work orders can be edited or deleted?', 'options' => [
                        'Draft work orders' => true,
                        'Released work orders' => false,
                        'Completed work orders' => false,
                        'Closed work orders' => false,
                    ]],
                    ['q' => 'From which statuses can a work order be started?', 'options' => [
                        'Released or paused' => true,
                        'Draft' => false,
                        'Completed' => false,
                        'Closed' => false,
                    ]],
                    ['q' => 'Can more material be issued than the remaining planned quantity?', 'options' => [
                        'No — the plan must be increased first, with a note' => true,
                        'Yes, without limit' => false,
                        'Only on night shift' => false,
                        'Only for screws' => false,
                    ]],
                    ['q' => 'You return 3 sheets but only 2 were issued. What happens?', 'options' => [
                        'It is refused — you can\'t return more than was issued' => true,
                        'Stock increases by 3' => false,
                        'The BOM is deleted' => false,
                        'Nothing' => false,
                    ]],
                    ['q' => 'A cost line of 12 hours at ₦2,500 adds how much cost?', 'options' => [
                        '₦30,000' => true,
                        '₦2,500' => false,
                        '₦14,500' => false,
                        '₦12,000' => false,
                    ]],
                    ['q' => 'Materials ₦900,000, cost lines ₦300,000, 8 good, 2 scrap. What is the finished-goods unit cost?', 'options' => [
                        '₦150,000' => true,
                        '₦120,000' => false,
                        '₦112,500' => false,
                        '₦1,200,000' => false,
                    ]],
                    ['q' => 'In the same job (8 good, 2 scrap), what is the yield?', 'options' => [
                        '80%' => true,
                        '20%' => false,
                        '100%' => false,
                        '60%' => false,
                    ]],
                    ['q' => 'Which work orders can be closed?', 'options' => [
                        'Completed work orders' => true,
                        'Draft work orders' => false,
                        'Paused work orders' => false,
                        'Any work order' => false,
                    ]],
                    ['q' => 'Wardrobes consistently cost 15% more to make than quoted. What should happen?', 'options' => [
                        'Share it with estimators so catalogue rates and templates are updated' => true,
                        'Nothing' => false,
                        'Hide it from the estimators' => false,
                        'Stop recording scrap' => false,
                    ]],
                    ['q' => 'Select ALL the parts of a good weekly workshop review.', 'options' => [
                        'Late work orders and scrap' => true,
                        'Material overruns and blockers' => true,
                        'Asking "why" until the root cause is found' => true,
                        'Blaming individuals without fixing the process' => false,
                    ]],
                ],
            ],
        ],
    ],

    'recommended_for' => ['Production Supervisor', 'Storekeeper'],
];
