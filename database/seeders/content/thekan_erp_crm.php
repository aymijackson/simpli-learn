<?php

/*
 * "CRM & Customer Care in Thekan ERP" — content for ThekanServicesSeeder.
 *
 * Global customer-relationship practice and the CRM module feature by
 * feature: leads and sources, customers and sites, opportunities and the
 * pipeline, interactions, activities and notes, support tickets, and the
 * analytics and customer segmentation built on them.
 *
 * Statuses, rules and segmentation thresholds match Thekan ERP as built.
 *
 * Quiz format: 'q' => question, 'options' => [text => is_correct, ...].
 * More than one correct option makes it a "select all that apply" question.
 */

return [
    'test_prefix' => 'erp-crm',
    'test_label' => 'CRM',

    'course' => [
        'title' => 'CRM & Customer Care in Thekan ERP',
        'slug' => 'thekan-erp-crm',
        'description' => <<<'HTML'
<p>Thekan's clients — government agencies, developers, hotels, corporate offices and private homeowners at the top of the market — choose us because they trust us. That trust is built in every call, meeting, follow-up and after-sales visit. This course shows you how to manage those relationships professionally, and how to do it in the <strong>CRM</strong> module of Thekan ERP.</p>
<p>You'll learn global practice for managing leads, pipelines and customer service, and the CRM feature by feature: leads and lead sources, customers and their sites, opportunities, interactions, activities, notes, support tickets, analytics and customer segmentation.</p>
<ul>
<li><strong>Who it's for:</strong> sales executives, estimators, project managers and anyone who deals with clients or handles their requests and complaints.</li>
<li><strong>Before you start:</strong> complete <em>Thekan ERP Foundations</em>. This course opens once you've passed it.</li>
<li><strong>How it works:</strong> 6 modules, each ending with a test you must pass to unlock the next. The last module ends with the final assessment.</li>
<li><strong>To complete it:</strong> finish every lesson and score at least 80% on the final assessment. You'll receive a certificate.</li>
</ul>
HTML,
        'catalog' => [
            'subtitle' => 'Win and keep clients: leads, pipeline, follow-up and customer care — in Thekan ERP.',
            'category' => 'Thekan ERP',
            'level' => 'beginner',
            'duration_minutes' => 150,
            'instructor_name' => 'Thekan Services Limited',
            'instructor_bio' => 'Prepared by Thekan for everyone who works with clients, based on recognised sales and customer-service practice and on how Thekan ERP is built.',
            'outcomes' => [
                'Use CRM as a workflow, where every record implies a next action',
                'Capture, qualify and convert leads, and keep lead sources meaningful',
                'Run an honest pipeline of opportunities with stages, values, probabilities and owners',
                'Log interactions, plan activities and keep notes so no client is forgotten',
                'Handle support tickets from open to closed, to service standards',
                'Read CRM analytics and customer segments, and act on them',
            ],
        ],
    ],

    'modules' => [

        // ------------------------------------------------------------------
        [
            'title' => 'Module 1: Customer relationships — global best practice',
            'lessons' => [
                [
                    'title' => 'What a CRM is for',
                    'preview' => true,
                    'content' => <<<'HTML'
<p>A <strong>CRM</strong> (customer relationship management system) holds everything a company knows about its clients and prospects, and everything it has done and promised for them.</p>
<p>The best-run sales and service teams in the world treat their CRM not as a filing cabinet but as a <strong>workflow engine</strong>: every record implies a next action. A lead needs qualifying. An opportunity needs moving forward or closing. A call needs a follow-up. A complaint needs resolving.</p>
<h1>Why it matters</h1>
<ul>
<li><strong>No client is forgotten.</strong> Follow-ups don't depend on someone's memory or notebook.</li>
<li><strong>Anyone can pick up.</strong> If a colleague is on leave or leaves, the full history of every client is there.</li>
<li><strong>Honest forecasting.</strong> Management can see what work is likely to come in, and when.</li>
<li><strong>Service you can measure.</strong> How fast we respond, how long problems take to fix, which clients are unhappy.</li>
</ul>
<h1>The CRM records in Thekan ERP</h1>
<ul>
<li><strong>Lead</strong> — a potential client not yet qualified.</li>
<li><strong>Customer</strong> — the "source of truth" record of who we sell to and serve.</li>
<li><strong>Opportunity</strong> — a specific deal in the pipeline, with a value and a stage.</li>
<li><strong>Interaction</strong> — a touchpoint: call, email, meeting, message.</li>
<li><strong>Activity</strong> — a planned task: a follow-up, a site visit, a call-back.</li>
<li><strong>Note</strong> — internal context: decisions, agreements, next steps.</li>
<li><strong>Support ticket</strong> — a client's issue or request, tracked to resolution.</li>
</ul>
<blockquote><strong>If skipped steps aren't recorded, the analytics go blank or mislead.</strong> CRM only works when everyone logs what they do.</blockquote>
HTML,
                ],
                [
                    'title' => 'Thekan\'s clients and the customer lifecycle',
                    'content' => <<<'HTML'
<h1>Who our clients are</h1>
<ul>
<li><strong>Government and public institutions</strong> — formal procurement, strict documentation, several decision-makers, long timelines.</li>
<li><strong>Developers and contractors</strong> — repeat business, price-sensitive, programme-driven.</li>
<li><strong>Hotels, offices and corporates</strong> — fit-out and furniture to brand standards, often multiple sites.</li>
<li><strong>High-end private clients</strong> — personal service, discretion, high expectations on finish and responsiveness.</li>
</ul>
<h1>The customer lifecycle</h1>
<ol>
<li><strong>Lead</strong> — an enquiry, referral, tender notice or contact made at an event.</li>
<li><strong>Qualified</strong> — we've confirmed a real need, budget, decision-maker and timeline.</li>
<li><strong>Customer</strong> — their record becomes the source of truth.</li>
<li><strong>Opportunity</strong> — a deal in the pipeline, moving through stages to won or lost.</li>
<li><strong>Delivery</strong> — the project, with interactions logged throughout.</li>
<li><strong>Aftercare</strong> — support tickets, warranty, maintenance.</li>
<li><strong>Repeat and referral</strong> — happy clients come back and send others.</li>
</ol>
<h1>Professional conduct</h1>
<ul>
<li>Respond to every enquiry within one working day, even if only to acknowledge it.</li>
<li>Never over-promise on price, lead time or finish to win a deal.</li>
<li>Treat client information as confidential, and use personal data only for the purpose it was given — Nigeria's Data Protection Act 2023 requires it.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What does it mean to treat a CRM as a "workflow engine"?', 'options' => [
                    'Every record implies a next action — qualify, follow up, move forward or close' => true,
                    'It is just a place to store contact details' => false,
                    'Only managers use it' => false,
                    'It replaces the accounts' => false,
                ]],
                ['q' => 'What is the difference between an interaction and an activity?', 'options' => [
                    'An interaction is a touchpoint that happened; an activity is a planned task for the future' => true,
                    'They are the same' => false,
                    'Activities are only for managers' => false,
                    'Interactions are financial records' => false,
                ]],
                ['q' => 'Which CRM record is the "source of truth" for who we sell to and serve?', 'options' => [
                    'Customer' => true,
                    'Note' => false,
                    'Activity' => false,
                    'Lead source' => false,
                ]],
                ['q' => 'How quickly should every enquiry get a response?', 'options' => [
                    'Within one working day, even if only to acknowledge it' => true,
                    'Within a month' => false,
                    'Only if it looks profitable' => false,
                    'When the salesperson remembers' => false,
                ]],
                ['q' => 'Select ALL the benefits of keeping the CRM up to date.', 'options' => [
                    'No client is forgotten' => true,
                    'A colleague can pick up any client\'s history' => true,
                    'Honest forecasting of future work' => true,
                    'Fewer clients to serve' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 2: Leads — capture, qualify, convert',
            'lessons' => [
                [
                    'title' => 'Capturing leads and lead sources',
                    'content' => <<<'HTML'
<p>Every enquiry is a lead until it's qualified. Capture it in <strong>CRM → Leads</strong> the same day it arrives — a lead in someone's phone is a lead the company doesn't know about.</p>
<h1>A lead record</h1>
<ul>
<li><strong>Lead name</strong> (required) — the person or organisation.</li>
<li><strong>Email</strong>, <strong>phone</strong> and <strong>position</strong> (their job title).</li>
<li><strong>Company</strong> (required) — which Thekan company the lead belongs to.</li>
<li><strong>Source</strong> — chosen from the lead sources list.</li>
<li><strong>Status</strong> — <strong>new</strong>, <strong>contacted</strong>, <strong>qualified</strong>, <strong>converted</strong> or <strong>closed</strong>.</li>
<li><strong>Follow-up date</strong> and <strong>assigned to</strong> — who owns it and when they'll next act.</li>
<li><strong>Notes</strong>.</li>
</ul>
<h1>Lead sources</h1>
<p><strong>CRM → Lead Sources</strong> holds the list of where leads come from — for example Referral, Website, Instagram, Tender notice, Exhibition, Walk-in, Existing client. Sources can be switched on or off.</p>
<p>Recording the source honestly tells management which marketing works. If every lead says "Other", nobody can tell whether the exhibition stand paid for itself.</p>
<h1>Good habits</h1>
<ul>
<li>Search before creating — the person may already be a lead or a customer.</li>
<li>Always assign an owner and a follow-up date. A lead with no owner is a lead nobody will call.</li>
<li>Move the status as things happen: <em>new</em> → <em>contacted</em> once you've spoken to them.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Qualifying and converting leads',
                    'content' => <<<'HTML'
<h1>Qualification: is this a real opportunity?</h1>
<p>Sales teams worldwide use a simple check, often called <strong>BANT</strong>:</p>
<ul>
<li><strong>Budget</strong> — can they pay for what they want?</li>
<li><strong>Authority</strong> — are we talking to the decision-maker, or can we reach them?</li>
<li><strong>Need</strong> — is there a real, defined requirement we can meet?</li>
<li><strong>Timeline</strong> — when do they need it? Is it realistic?</li>
</ul>
<p>Qualifying early saves estimators days of work pricing jobs that were never going to happen. A qualified lead is one where the answers are known and good enough to invest time in.</p>
<h1>Converting</h1>
<ul>
<li>When the lead is qualified, select <strong>Convert</strong>. The lead's status becomes <strong>converted</strong>, the change is recorded, and the assigned employee is notified. A lead can only be converted once.</li>
<li>Converting marks the lead — it doesn't create the customer for you. Next, make sure the client exists as a <strong>Customer</strong> (search first; create it if it doesn't exist), then open an <strong>Opportunity</strong> for the deal.</li>
</ul>
<h1>Closing a lead</h1>
<p>Not every lead converts. If it's not a fit — no budget, wrong kind of work, can't reach a decision-maker — set the status to <strong>closed</strong> and note why. Closed leads with reasons are valuable information.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What does BANT stand for?', 'options' => [
                    'Budget, Authority, Need, Timeline' => true,
                    'Business, Accounts, Notes, Tasks' => false,
                    'Bills, Approvals, Numbers, Totals' => false,
                    'Buyer, Agent, Network, Territory' => false,
                ]],
                ['q' => 'What happens when you convert a lead?', 'options' => [
                    'Its status becomes converted and the owner is notified; you then make sure a customer and opportunity exist' => true,
                    'A posted invoice is created' => false,
                    'The lead is deleted' => false,
                    'A project is created automatically' => false,
                ]],
                ['q' => 'Why must every lead have an owner and a follow-up date?', 'options' => [
                    'A lead with no owner is a lead nobody will call' => true,
                    'The system won\'t display it otherwise' => false,
                    'For payroll' => false,
                    'It doesn\'t matter' => false,
                ]],
                ['q' => 'A lead has no budget and no decision-maker can be found. What should you do?', 'options' => [
                    'Set the status to closed and note why' => true,
                    'Convert it anyway' => false,
                    'Delete it without a trace' => false,
                    'Send them a quote immediately' => false,
                ]],
                ['q' => 'Select ALL the lead statuses in Thekan ERP.', 'options' => [
                    'New' => true,
                    'Contacted' => true,
                    'Qualified' => true,
                    'Invoiced' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 3: Customers and the pipeline',
            'lessons' => [
                [
                    'title' => 'Customer records and sites',
                    'content' => <<<'HTML'
<p>The <strong>Customer</strong> record is shared by CRM, Sales, Projects and Finance: the same customer appears on quotes, projects, invoices and statements. Keep it clean and there's one true picture of each client; let duplicates in and history, balances and reports split.</p>
<h1>What a customer holds</h1>
<ul>
<li>Identity and contact details, and the Thekan company it belongs to.</li>
<li><strong>Contacts</strong> — the people we deal with: decision-maker, project contact, accounts contact.</li>
<li><strong>Addresses</strong> — billing and correspondence.</li>
<li><strong>Sites</strong> — where we deliver and work. Sites can be linked to the Locations module, down to blocks, floors and rooms, so projects and installed items can be traced to the exact place.</li>
</ul>
<h1>Good practice</h1>
<ul>
<li><strong>Search before creating.</strong> Check spelling variations and abbreviations ("FCT Admin", "Federal Capital Territory Administration").</li>
<li>Record who the decision-makers are, not only the person who first called.</li>
<li>Add new sites to the existing customer rather than creating a new customer for each site.</li>
<li>Keep contacts current. A client who moved on two years ago shouldn't still be the main contact.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Opportunities and pipeline stages',
                    'content' => <<<'HTML'
<p>An <strong>opportunity</strong> is a specific deal we're pursuing with a customer: "Hotel X — 120 bedroom furniture packages", "Ministry Y — office fit-out, Block C". Create them under <strong>CRM → Opportunities</strong>.</p>
<h1>An opportunity record</h1>
<ul>
<li><strong>Title</strong>, <strong>customer</strong> and <strong>value</strong> (all required).</li>
<li><strong>Stage</strong> — <strong>Prospecting</strong>, <strong>Qualification</strong>, <strong>Proposal</strong>, <strong>Negotiation</strong>, then <strong>Won</strong> or <strong>Lost</strong>.</li>
<li><strong>Probability</strong> (0–100%) — how likely it is to be won.</li>
<li><strong>Close date</strong> — when we expect a decision.</li>
<li><strong>Owner</strong> (required) — the one person accountable for the deal.</li>
<li><strong>Notes</strong>.</li>
</ul>
<h1>What the stages mean at Thekan</h1>
<ul>
<li><strong>Prospecting</strong> — we've identified the opportunity; not yet confirmed.</li>
<li><strong>Qualification</strong> — confirming budget, authority, need and timeline; site visit and brief.</li>
<li><strong>Proposal</strong> — BoQ or quote prepared and sent.</li>
<li><strong>Negotiation</strong> — discussing price, scope, terms or design changes.</li>
<li><strong>Won</strong> / <strong>Lost</strong> — use <strong>Mark Won</strong> or <strong>Mark Lost</strong>. The change is recorded and the owner is notified.</li>
</ul>
<p>Each move to a new stage should be backed by evidence — an interaction or a note showing what happened. That's what stops a "fake pipeline" of deals that aren't really moving.</p>
HTML,
                ],
                [
                    'title' => 'Forecasting honestly',
                    'content' => <<<'HTML'
<h1>The weighted pipeline</h1>
<p>Management uses the pipeline to plan workshop capacity, site teams, cash and purchasing. The standard method is the <strong>weighted pipeline</strong>: each opportunity's value × its probability.</p>
<ul>
<li>₦50m at 20% (prospecting) → ₦10m weighted.</li>
<li>₦30m at 60% (negotiation) → ₦18m weighted.</li>
<li>₦20m at 90% (verbally agreed) → ₦18m weighted.</li>
<li>Total weighted pipeline: <strong>₦46m</strong>, even though the full value is ₦100m.</li>
</ul>
<h1>Pipeline discipline</h1>
<ul>
<li><strong>Every opportunity has an owner.</strong> No owner means an orphan deal nobody is chasing.</li>
<li><strong>Close dates are real.</strong> If the date passes, update it — or decide the deal is lost.</li>
<li><strong>Probabilities are honest.</strong> Optimism makes forecasts useless.</li>
<li><strong>Stale deals are cleared.</strong> An opportunity with no interaction for months is probably dead; mark it lost and record why.</li>
<li><strong>Lost reasons are recorded.</strong> Price? Timing? Design? Competitor? Patterns show what to fix.</li>
</ul>
<blockquote>A smaller, honest pipeline is worth far more to the business than a large one full of hope.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'A client has three sites. How should they be recorded?', 'options' => [
                    'One customer with three sites' => true,
                    'Three separate customers' => false,
                    'Only the head-office address' => false,
                    'In chat' => false,
                ]],
                ['q' => 'Which are the opportunity stages, in order?', 'options' => [
                    'Prospecting, Qualification, Proposal, Negotiation, then Won or Lost' => true,
                    'Draft, Posted, Void' => false,
                    'New, Contacted, Converted' => false,
                    'Open, Pending, Resolved, Closed' => false,
                ]],
                ['q' => 'An opportunity worth ₦40m has a 25% probability. What is its weighted value?', 'options' => [
                    '₦10m' => true,
                    '₦40m' => false,
                    '₦25m' => false,
                    '₦4m' => false,
                ]],
                ['q' => 'An opportunity\'s close date has passed and the client isn\'t responding. What should the owner do?', 'options' => [
                    'Follow up; then update the date or mark it lost with the reason' => true,
                    'Leave it as it is' => false,
                    'Raise the probability' => false,
                    'Delete it' => false,
                ]],
                ['q' => 'Select ALL the required fields on an opportunity.', 'options' => [
                    'Title' => true,
                    'Customer and value' => true,
                    'Stage and owner' => true,
                    'The client\'s bank PIN' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 4: Interactions, activities and notes',
            'lessons' => [
                [
                    'title' => 'Interactions: proof of engagement',
                    'content' => <<<'HTML'
<p>An <strong>interaction</strong> records a touchpoint that has happened. Log them under <strong>CRM → Interactions</strong>, or from the lead, customer or opportunity itself.</p>
<h1>An interaction record</h1>
<ul>
<li><strong>Subject</strong> and <strong>details</strong> — what was discussed and, above all, the <strong>outcome</strong> and next step.</li>
<li><strong>Type</strong> — <strong>call</strong>, <strong>email</strong>, <strong>meeting</strong>, <strong>message</strong> (for example WhatsApp) or <strong>other</strong>.</li>
<li><strong>Date</strong> and the <strong>employee</strong> who had it.</li>
<li>What it's about — a <strong>customer</strong>, <strong>lead</strong> or <strong>opportunity</strong>.</li>
</ul>
<h1>Why it matters</h1>
<ul>
<li>Interactions are proof of engagement and relationship health. The CRM uses them to tell which customers are <em>warm</em> and which have gone <em>dormant</em>.</li>
<li>An interaction with no outcome has little value. "Called client" tells nobody anything; "Called Mrs Bello — approved kitchen layout B, wants revised price for quartz worktop by Friday" tells the whole team what to do next.</li>
</ul>
<blockquote><strong>Log it the same day.</strong> If you need to follow up, create an activity straight away.</blockquote>
HTML,
                ],
                [
                    'title' => 'Activities and notes',
                    'content' => <<<'HTML'
<h1>Activities: what happens next</h1>
<p>An <strong>activity</strong> is a planned piece of follow-up work, under <strong>CRM → Activities</strong>:</p>
<ul>
<li><strong>Subject</strong> and description — for example "Send revised quote — quartz worktops".</li>
<li><strong>Type</strong> — <strong>call</strong>, <strong>meeting</strong>, <strong>email</strong> or <strong>task</strong>.</li>
<li><strong>Due date</strong> (required) and <strong>owner</strong> (required).</li>
<li><strong>Status</strong> — <strong>pending</strong>, <strong>completed</strong> or <strong>overdue</strong>.</li>
<li>Optionally linked to the lead or opportunity it relates to.</li>
</ul>
<p>Activities are the team's daily work queue. Overdue activities are a warning sign: clients are waiting for something we promised.</p>
<h1>Notes: the context</h1>
<p>A <strong>note</strong> records internal context — decisions, agreements, sensitivities and next steps — against a customer, lead or opportunity. Each has a subject, content and author.</p>
<ul>
<li>"Client prefers WhatsApp to email; decisions need sign-off from the Permanent Secretary."</li>
<li>"Agreed 50% deposit instead of 60% in exchange for a fixed delivery date."</li>
</ul>
<p>Notes stop knowledge being lost when people are away or move on, and make handovers to the project team smooth.</p>
HTML,
                ],
                [
                    'title' => 'A client-facing person\'s daily routine',
                    'content' => <<<'HTML'
<ol>
<li><strong>Morning:</strong> open your activities — today's and any overdue — and your leads with follow-up dates due.</li>
<li><strong>Do the work:</strong> calls, emails, site visits, quotes.</li>
<li><strong>After each contact:</strong> log the interaction with its outcome; update the lead status or opportunity stage if it moved; mark the activity completed.</li>
<li><strong>Create the next activity</strong> before you leave the record — every live client should have a next step with a date.</li>
<li><strong>Record anything important</strong> as a note.</li>
<li><strong>End of week:</strong> review your opportunities — close dates, probabilities and stale deals.</li>
</ol>
<h1>When CRM and tasks meet</h1>
<p>Work that someone else must do — the estimator pricing a revision, a designer updating drawings — goes on a <strong>task</strong> linked to the CRM lead, opportunity or customer (as you learned in Foundations), so it has an owner, a due date and a supervisor.</p>
<blockquote>Ten minutes a day keeping CRM current is how a sales team remembers every promise it makes.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Which interaction record is most useful?', 'options' => [
                    '"Called Mrs Bello — approved layout B, wants revised price for quartz worktop by Friday"' => true,
                    '"Called client"' => false,
                    '"Chat"' => false,
                    '"Meeting held"' => false,
                ]],
                ['q' => 'What is required on every CRM activity?', 'options' => [
                    'A subject, a type, a due date and an owner' => true,
                    'A posted invoice' => false,
                    'A support ticket' => false,
                    'Nothing' => false,
                ]],
                ['q' => 'Where should you record "Decisions need sign-off from the Permanent Secretary"?', 'options' => [
                    'As a note on the customer or opportunity' => true,
                    'Only in your memory' => false,
                    'In the BoQ quantities' => false,
                    'As a lead source' => false,
                ]],
                ['q' => 'The estimator needs to price a revision for a client. How should this be handed over?', 'options' => [
                    'As a task linked to the CRM opportunity, with an owner and due date' => true,
                    'By shouting across the office' => false,
                    'As an interaction' => false,
                    'It shouldn\'t be recorded' => false,
                ]],
                ['q' => 'Select ALL the interaction types in Thekan ERP.', 'options' => [
                    'Call' => true,
                    'Email and meeting' => true,
                    'Message' => true,
                    'Invoice' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 5: Customer care and support tickets',
            'lessons' => [
                [
                    'title' => 'Support tickets from open to closed',
                    'content' => <<<'HTML'
<p>A <strong>support ticket</strong> is a client's issue or request — a door that won't close, a scratched worktop, a question about an invoice, a request for an extra item. Tickets create a service obligation, and are how we measure the quality of our service. Manage them under <strong>CRM → Support Tickets</strong>.</p>
<h1>A ticket</h1>
<ul>
<li><strong>Subject</strong> and <strong>description</strong> (required) — what's wrong, where, since when.</li>
<li><strong>Customer</strong> (required).</li>
<li><strong>Priority</strong> — <strong>low</strong>, <strong>medium</strong>, <strong>high</strong> or <strong>urgent</strong>.</li>
<li><strong>Channel</strong> — <strong>web</strong>, <strong>email</strong>, <strong>phone</strong>, <strong>WhatsApp</strong> or <strong>other</strong>.</li>
<li><strong>Category</strong> — for example billing, technical, sales or other.</li>
<li><strong>Assigned to</strong> — the person responsible.</li>
<li><strong>Status</strong> — <strong>open</strong>, <strong>pending</strong>, <strong>resolved</strong> or <strong>closed</strong>.</li>
</ul>
<h1>Working the ticket</h1>
<ol>
<li><strong>Assign and respond</strong> — the first comment is the first response: acknowledge, and say what happens next.</li>
<li><strong>Work and update</strong> — add <strong>comments</strong> as things progress, and upload <strong>attachments</strong> (photos, reports; up to 5 MB each) as evidence.</li>
<li><strong>Pending</strong> — waiting on the client or a third party.</li>
<li><strong>Resolve</strong> — record what fixed it. A ticket can only be resolved once.</li>
<li><strong>Close</strong> — once the client confirms they're satisfied.</li>
</ol>
<blockquote><strong>Every complaint is a chance to earn trust.</strong> Clients remember how quickly and honestly we fixed a problem far longer than the problem itself.</blockquote>
HTML,
                ],
                [
                    'title' => 'Service standards and aftercare',
                    'content' => <<<'HTML'
<h1>Service standards</h1>
<p>Good service organisations set and measure <strong>service levels</strong>:</p>
<ul>
<li><strong>First response time</strong> — how fast the client hears from us. Aim for the same working day; urgent tickets within hours.</li>
<li><strong>Resolution time</strong> — how long until it's fixed.</li>
<li><strong>Backlog</strong> — how many tickets are open, and how old they are.</li>
<li><strong>Reopen rate</strong> — how often "fixed" problems come back.</li>
</ul>
<p>The ticket analytics show these, broken down by agent, trend and age, and can be exported.</p>
<h1>Aftercare for furniture and construction</h1>
<ul>
<li>Agree a <strong>defects period</strong> and warranty at handover, and tell the client how to report issues.</li>
<li>Log every defect report as a ticket, even if it's fixed on the spot — the history shows patterns (a hinge type that keeps failing, a finish that marks easily).</li>
<li>Bigger repairs or maintenance work become tasks or a maintenance project in <em>Projects &amp; Maintenance</em>.</li>
<li>Feed recurring problems back to design and production so we stop repeating them.</li>
</ul>
<h1>Handling difficult conversations</h1>
<ul>
<li>Listen fully, acknowledge the problem, and don't argue about blame on the first call.</li>
<li>Give a clear next step and a time — then meet it.</li>
<li>Escalate early to a manager when a high-value or government client is unhappy.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What is the first response on a support ticket?', 'options' => [
                    'The first comment acknowledging the issue and saying what happens next' => true,
                    'Closing the ticket' => false,
                    'Deleting the ticket' => false,
                    'Sending an invoice' => false,
                ]],
                ['q' => 'A ticket is waiting for the client to give site access. Which status fits?', 'options' => [
                    'Pending' => true,
                    'Closed' => false,
                    'Resolved' => false,
                    'Urgent' => false,
                ]],
                ['q' => 'A fitter fixes a loose hinge on the spot during a visit. Should a ticket be logged?', 'options' => [
                    'Yes — the history shows patterns such as a hinge type that keeps failing' => true,
                    'No — it\'s fixed, so it doesn\'t matter' => false,
                    'Only if the client pays' => false,
                    'Only for government clients' => false,
                ]],
                ['q' => 'When should a ticket be closed?', 'options' => [
                    'Once it is resolved and the client confirms they\'re satisfied' => true,
                    'As soon as it is opened' => false,
                    'When the agent is busy' => false,
                    'Never' => false,
                ]],
                ['q' => 'Select ALL the service-level measures.', 'options' => [
                    'First response time' => true,
                    'Resolution time' => true,
                    'Reopen rate' => true,
                    'Number of chat emojis' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 6: Analytics, segmentation and data quality',
            'lessons' => [
                [
                    'title' => 'CRM analytics and customer segments',
                    'content' => <<<'HTML'
<h1>CRM analytics</h1>
<p>Under <strong>CRM Analytics</strong> managers can review leads, opportunities, activities, notes, interactions, support tickets and customers, and the <strong>CRM Executive</strong> dashboard brings them together. Use them to see conversion rates, pipeline by stage, overdue follow-ups and service performance.</p>
<h1>Customer segmentation</h1>
<p><strong>Customer Segmentation</strong> sorts every customer into a segment, checked in this order — the first rule that fits wins:</p>
<ol>
<li><strong>Hot / High Value</strong> — open pipeline value of ₦50,000 or more.</li>
<li><strong>At Risk</strong> — 2 or more open support tickets.</li>
<li><strong>Warm</strong> — an interaction within the last 60 days and at least 2 interactions in the last 30 days.</li>
<li><strong>New</strong> — created within the last 14 days.</li>
<li><strong>Dormant</strong> — no interaction at all, or none for 60 days or more.</li>
<li><strong>Normal</strong> — everyone else.</li>
</ol>
<p>The thresholds can be adjusted on the screen, and saved as <strong>Segment Presets</strong> for reuse.</p>
<h1>Acting on segments</h1>
<ul>
<li><strong>Hot</strong> — make sure every one has an owner, a next activity and a realistic close date.</li>
<li><strong>At Risk</strong> — a manager reviews the open tickets and calls the client.</li>
<li><strong>Dormant</strong> — plan a check-in: a visit, a new catalogue, a maintenance offer. Past clients are the cheapest new work.</li>
</ul>
<blockquote>Segments are only as good as the logging behind them. A client you speak to every week shows as Dormant if no one records the interactions.</blockquote>
HTML,
                ],
                [
                    'title' => 'Data quality and privacy',
                    'content' => <<<'HTML'
<h1>Data quality rules</h1>
<ul>
<li>Search before you create a lead or customer.</li>
<li>Fill in the fields that drive the workflow: owner, follow-up date, stage, close date, source.</li>
<li>Write outcomes, not just events.</li>
<li>Keep statuses current — a lead "new" for six months, or an opportunity "proposal" for a year, distorts every report.</li>
<li>Record lost and closed reasons.</li>
</ul>
<h1>Privacy</h1>
<p>CRM holds personal data — names, phone numbers, emails, home addresses of private clients. Nigeria's Data Protection Act 2023 expects us to:</p>
<ul>
<li>use it only for the purpose it was given — serving that client, not unrelated marketing without consent;</li>
<li>keep it accurate and secure, and not share it outside the company without a lawful basis;</li>
<li>never export client lists to personal devices, email or chat groups.</li>
</ul>
<h1>Permissions</h1>
<p>CRM access is controlled by permission — view, create, update and delete for each record type, plus analytics. If you need access you don't have, ask your manager.</p>
<p>Mark this lesson complete, then take the final assessment: 20 questions in 30 minutes. You need 80% to pass, with up to 3 attempts.</p>
HTML,
                ],
            ],
            'final' => [
                'title' => 'CRM & Customer Care — Final Assessment',
                'slug' => 'erp-crm-assessment',
                'description' => '<p>The final assessment for CRM &amp; Customer Care in Thekan ERP. It covers relationship practice, leads, the pipeline, engagement, support tickets and analytics.</p>',
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
                    ['q' => 'A potential client calls about furnishing a new office but hasn\'t confirmed budget or timing. What is it in the CRM?', 'options' => [
                        'A lead' => true,
                        'A customer with a won opportunity' => false,
                        'A support ticket' => false,
                        'A posted invoice' => false,
                    ]],
                    ['q' => 'Why should lead sources be recorded honestly?', 'options' => [
                        'They show management which marketing actually brings in work' => true,
                        'They set the VAT rate' => false,
                        'They decide the lead owner' => false,
                        'They aren\'t used for anything' => false,
                    ]],
                    ['q' => 'Which two fields stop a lead from being forgotten?', 'options' => [
                        'Assigned to (owner) and follow-up date' => true,
                        'Position and email' => false,
                        'Notes and source' => false,
                        'Company and phone' => false,
                    ]],
                    ['q' => 'You convert a qualified lead. What should you do next?', 'options' => [
                        'Make sure the client exists as a customer (search first), then open an opportunity' => true,
                        'Nothing — conversion creates everything automatically' => false,
                        'Delete the lead' => false,
                        'Send an invoice' => false,
                    ]],
                    ['q' => 'Can a lead be converted twice?', 'options' => [
                        'No — the ERP refuses a lead that is already converted' => true,
                        'Yes, as often as needed' => false,
                        'Only by a manager' => false,
                        'Only on weekends' => false,
                    ]],
                    ['q' => 'A long-standing developer client opens a new site. What should you do?', 'options' => [
                        'Add the site to the existing customer' => true,
                        'Create a new customer for the site' => false,
                        'Store the site address in chat' => false,
                        'Ignore the site details' => false,
                    ]],
                    ['q' => 'At which stage has a BoQ or quote usually been sent?', 'options' => [
                        'Proposal' => true,
                        'Prospecting' => false,
                        'Qualification' => false,
                        'Lost' => false,
                    ]],
                    ['q' => 'What is an "orphan deal"?', 'options' => [
                        'An opportunity with no owner, so nobody is chasing it' => true,
                        'A deal for a children\'s home' => false,
                        'A won opportunity' => false,
                        'A closed lead' => false,
                    ]],
                    ['q' => 'Opportunities: ₦60m at 50%, ₦20m at 10%. What is the weighted pipeline?', 'options' => [
                        '₦32m' => true,
                        '₦80m' => false,
                        '₦60m' => false,
                        '₦8m' => false,
                    ]],
                    ['q' => 'What should back up each move of an opportunity to a new stage?', 'options' => [
                        'Evidence — an interaction or note showing what happened' => true,
                        'Nothing' => false,
                        'A new customer record' => false,
                        'A support ticket' => false,
                    ]],
                    ['q' => 'What is the single most important part of an interaction record?', 'options' => [
                        'The outcome and next step' => true,
                        'The font' => false,
                        'The time to the second' => false,
                        'The weather' => false,
                    ]],
                    ['q' => 'Which activity statuses exist?', 'options' => [
                        'Pending, completed, overdue' => true,
                        'Draft, posted, void' => false,
                        'Open, resolved, closed' => false,
                        'Prospecting, won, lost' => false,
                    ]],
                    ['q' => 'Select ALL the good habits after each client contact.', 'options' => [
                        'Log the interaction with its outcome' => true,
                        'Update the lead status or opportunity stage if it moved' => true,
                        'Create the next activity with a date' => true,
                        'Wait until month-end to record it' => false,
                    ]],
                    ['q' => 'Select ALL the support ticket priorities.', 'options' => [
                        'Low and medium' => true,
                        'High' => true,
                        'Urgent' => true,
                        'Optional' => false,
                    ]],
                    ['q' => 'What should be recorded when a ticket is resolved?', 'options' => [
                        'What fixed it' => true,
                        'Nothing' => false,
                        'The agent\'s password' => false,
                        'A new lead' => false,
                    ]],
                    ['q' => 'A high-value government client has three open complaints and is unhappy. What is best practice?', 'options' => [
                        'Escalate early to a manager, who reviews the tickets and calls the client' => true,
                        'Wait for them to calm down' => false,
                        'Close the tickets to clear the backlog' => false,
                        'Argue about who is to blame' => false,
                    ]],
                    ['q' => 'A customer has ₦2m of open pipeline and 3 open tickets. Which segment are they in?', 'options' => [
                        'Hot / High Value — that rule is checked first' => true,
                        'At Risk' => false,
                        'Dormant' => false,
                        'New' => false,
                    ]],
                    ['q' => 'A customer with no open pipeline has had no logged interaction for 90 days and no open tickets. Which segment?', 'options' => [
                        'Dormant' => true,
                        'Warm' => false,
                        'Hot / High Value' => false,
                        'At Risk' => false,
                    ]],
                    ['q' => 'You speak to a client weekly but they show as Dormant. Why?', 'options' => [
                        'The interactions aren\'t being logged in the CRM' => true,
                        'The client is unhappy' => false,
                        'Segmentation is random' => false,
                        'The client has too many opportunities' => false,
                    ]],
                    ['q' => 'A colleague asks you to export all private clients\' phone numbers to a WhatsApp group for a promotion. What should you do?', 'options' => [
                        'Decline — personal data must be used only for its purpose and not shared without a lawful basis' => true,
                        'Do it — it\'s good marketing' => false,
                        'Do it, but only the VIP clients' => false,
                        'Email the list to your personal account first' => false,
                    ]],
                ],
            ],
        ],
    ],

    // Recommended to everyone who deals directly with clients.
    'recommended_for' => ['Sales Executive', 'Estimator', 'Project Manager'],
];
