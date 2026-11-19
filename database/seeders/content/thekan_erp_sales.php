<?php

/*
 * "Sales & Estimating in Thekan ERP" — content for ThekanServicesSeeder.
 *
 * Global estimating and quote-to-cash practice, Thekan's commercial terms,
 * and the Sales module feature by feature: the quote catalogue, templates,
 * price lists and pricing rules, Bills of Quantities, sales quotes and what
 * happens after a win, then orders, deliveries, invoices, payments and
 * credit notes.
 *
 * Statuses, rules and calculations match Thekan ERP as built.
 *
 * Quiz format: 'q' => question, 'options' => [text => is_correct, ...].
 * More than one correct option makes it a "select all that apply" question.
 */

return [
    'test_prefix' => 'erp-sales',
    'test_label' => 'Sales',

    'course' => [
        'title' => 'Sales & Estimating in Thekan ERP',
        'slug' => 'thekan-erp-sales',
        'description' => <<<'HTML'
<p>Every Thekan job starts with a price. Price it too high and we lose the work; too low and we lose money on every cabinet we install. This course teaches how to estimate and quote well, and how to take a job from Bill of Quantities to cash in Thekan ERP.</p>
<p>You'll learn global estimating practice — measurement, unit rates, margin and risk — the commercial terms that protect Thekan, and the <strong>Sales</strong> module feature by feature: the quote catalogue, templates, price lists and pricing rules, BoQs, sales quotes, what to do when a quote is won, and the order-to-cash flow of orders, deliveries, invoices, payments and credit notes.</p>
<ul>
<li><strong>Who it's for:</strong> estimators, sales executives and quantity surveyors — and anyone who prepares, approves or follows up quotes and invoices.</li>
<li><strong>Before you start:</strong> complete <em>Thekan ERP Foundations</em>. This course opens once you've passed it.</li>
<li><strong>How it works:</strong> 7 modules, each ending with a test you must pass to unlock the next. The last module ends with the final assessment.</li>
<li><strong>To complete it:</strong> finish every lesson and score at least 80% on the final assessment. You'll receive a certificate.</li>
</ul>
HTML,
        'catalog' => [
            'subtitle' => 'Price jobs accurately, quote fast and consistently, and turn wins into cash — in Thekan ERP.',
            'category' => 'Thekan ERP',
            'level' => 'intermediate',
            'duration_minutes' => 210,
            'instructor_name' => 'Thekan Services Limited',
            'instructor_bio' => 'Prepared by Thekan for its estimating and sales teams, based on recognised estimating and quote-to-cash practice and on how Thekan ERP is built.',
            'outcomes' => [
                'Build unit rates from materials, labour, overheads and margin — and know margin from markup',
                'Set commercial terms that protect Thekan: validity, upfront payment, lead time, VAT and exclusions',
                'Prepare quotes fast and consistently with the catalogue, templates, price lists and pricing rules',
                'Produce and issue Bills of Quantities, and build, send and follow up sales quotes',
                'Hand a won quote over correctly — to a sales order or a project',
                'Run orders, deliveries, invoices, payments and credit notes with the right controls',
            ],
        ],
    ],

    'modules' => [

        // ------------------------------------------------------------------
        [
            'title' => 'Module 1: Estimating and selling — global best practice',
            'lessons' => [
                [
                    'title' => 'What good estimating looks like',
                    'preview' => true,
                    'content' => <<<'HTML'
<p>An <strong>estimate</strong> is our best forecast of what a job will cost us to deliver. A <strong>quote</strong> (or tender) is the price we offer the client, built on that estimate. Good firms keep the two clearly separate: the estimate is about cost and risk; the quote adds margin and terms.</p>
<h1>The estimating process used worldwide</h1>
<ol>
<li><strong>Understand the scope.</strong> Read the brief, drawings and specification; visit the site; list what is <em>not</em> included.</li>
<li><strong>Measure.</strong> Take off quantities from the drawings in consistent units — numbers of items, linear metres of worktop, square metres of wall finish. Construction uses standard methods of measurement (such as the RICS <em>New Rules of Measurement</em>) so quantities mean the same thing to everyone.</li>
<li><strong>Price.</strong> Build a rate for each item: materials, labour, equipment, subcontract, plus overheads.</li>
<li><strong>Assess risk.</strong> What could cost more than planned? Price it, exclude it or add a contingency.</li>
<li><strong>Add margin</strong> and set the commercial terms.</li>
<li><strong>Review.</strong> A second person checks quantities, rates and arithmetic before anything goes to the client.</li>
<li><strong>Learn.</strong> After the job, compare actual cost with the estimate and update your rates.</li>
</ol>
<h1>The marks of a good estimate</h1>
<ul>
<li><strong>Complete</strong> — nothing in scope missed: delivery, installation, protection, cleaning, waste disposal.</li>
<li><strong>Traceable</strong> — every number can be explained: where the quantity came from and how the rate was built.</li>
<li><strong>Consistent</strong> — the same item is priced the same way on every job, unless there is a reason.</li>
<li><strong>Honest about risk</strong> — assumptions and exclusions are written down, not kept in the estimator's head.</li>
</ul>
<blockquote><strong>The most expensive estimating mistakes are omissions,</strong> not wrong rates. Use checklists and templates so that delivery, installation, ironmongery and finishing are never forgotten.</blockquote>
HTML,
                ],
                [
                    'title' => 'Building a rate, and margin versus markup',
                    'content' => <<<'HTML'
<h1>Building up a unit rate</h1>
<p>A unit rate is the cost of one unit of an item — one wardrobe, one linear metre of kitchen base unit, one square metre of tiling. Build it from:</p>
<ul>
<li><strong>Materials</strong> — boards, edging, hardware, finishes, adhesives — with a <strong>waste allowance</strong> for offcuts and breakage (often 10–15% on sheet materials).</li>
<li><strong>Labour</strong> — workshop and site hours × the cost per hour of the people doing it.</li>
<li><strong>Equipment and consumables</strong> — machine time, blades, sanding discs, fixings.</li>
<li><strong>Subcontract</strong> — specialist work bought in (for example, glass, stone worktops, spray finishing).</li>
<li><strong>Overheads</strong> — a share of the costs of running the business: workshop rent, supervision, transport, design time.</li>
</ul>
<h1>Margin and markup are not the same</h1>
<p><strong>Markup</strong> is profit as a percentage of <em>cost</em>. <strong>Margin</strong> is profit as a percentage of the <em>selling price</em>. Mixing them up is one of the commonest pricing errors in business.</p>
<ul>
<li>Cost ₦100,000, selling price ₦125,000: profit ₦25,000.</li>
<li>Markup = 25,000 ÷ 100,000 = <strong>25%</strong>.</li>
<li>Margin = 25,000 ÷ 125,000 = <strong>20%</strong>.</li>
</ul>
<p>To hit a target <strong>margin</strong>, divide cost by (1 − margin): for a 25% margin on ₦100,000 of cost, price = 100,000 ÷ 0.75 = <strong>₦133,333</strong>. Adding 25% to cost (₦125,000) only gives a 20% margin.</p>
<blockquote><strong>Discounts come straight out of margin.</strong> On a 20% margin, a 10% discount halves your profit. Know your floor before you negotiate.</blockquote>
HTML,
                ],
                [
                    'title' => 'Commercial terms that protect Thekan',
                    'content' => <<<'HTML'
<p>The price is only half the quote. The terms decide whether we get paid, when, and what happens when things change. Thekan's quotes always set out:</p>
<ul>
<li><strong>Validity</strong> — how long the price holds ("valid until"). Material prices and exchange rates move; a quote without a validity date is an open-ended promise.</li>
<li><strong>Upfront payment</strong> — the deposit required before we order materials and start manufacture. For bespoke furniture, never start without it: made-to-measure items can't be sold to anyone else.</li>
<li><strong>Lead time</strong> — how long from approval and deposit to delivery or completion. Be realistic; the client will hold you to it.</li>
<li><strong>VAT</strong> — Nigerian VAT (7.5% unless the law or the client's status says otherwise), shown clearly.</li>
<li><strong>Delivery and installation</strong> — stated as its own amount, so the client sees what they're paying for.</li>
<li><strong>Inclusions, exclusions and assumptions</strong> — for example: "Excludes electrical and plumbing connections", "Assumes walls are plumb and plastered", "Assumes lift access to Floor 3".</li>
<li><strong>Variations</strong> — any change to the approved design or quantities is quoted and approved before we do it.</li>
</ul>
<h1>Government and high-end clients</h1>
<ul>
<li>Follow the client's procurement rules exactly — format, deadlines, required documents. A late or incomplete bid is usually rejected outright.</li>
<li>Keep pricing confidential. Never share one client's prices with another.</li>
<li>Agree payment milestones that match delivery (deposit, delivery, installation, completion), and any retention.</li>
</ul>
<blockquote>Unclear terms are where disputes start. If a reasonable client could read a line two ways, rewrite it.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What is the difference between an estimate and a quote?', 'options' => [
                    'The estimate forecasts our cost; the quote is the price offered, adding margin and terms' => true,
                    'They are the same thing' => false,
                    'The quote is prepared first, then the estimate' => false,
                    'The estimate is sent to the client; the quote is internal' => false,
                ]],
                ['q' => 'A job costs ₦100,000 and you need a 25% margin. What should the price be?', 'options' => [
                    'About ₦133,333 (cost ÷ 0.75)' => true,
                    '₦125,000 (cost + 25%)' => false,
                    '₦75,000' => false,
                    '₦100,025' => false,
                ]],
                ['q' => 'What are the most expensive estimating mistakes?', 'options' => [
                    'Omissions — items in scope that were never priced' => true,
                    'Rounding to the nearest naira' => false,
                    'Using a template' => false,
                    'Showing VAT separately' => false,
                ]],
                ['q' => 'Why should bespoke furniture never be started without the upfront payment?', 'options' => [
                    'Made-to-measure items can\'t be sold to anyone else if the client walks away' => true,
                    'The workshop needs a day off' => false,
                    'VAT is due first' => false,
                    'It isn\'t important' => false,
                ]],
                ['q' => 'Select ALL the terms every Thekan quote should state.', 'options' => [
                    'Validity date' => true,
                    'Upfront payment and lead time' => true,
                    'Inclusions, exclusions and assumptions' => true,
                    'Other clients\' prices for comparison' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 2: Setting up for fast, consistent quotes',
            'lessons' => [
                [
                    'title' => 'The quote catalogue',
                    'content' => <<<'HTML'
<p>The <strong>Quote Catalog</strong> (Sales → Quote Catalog) is Thekan's library of standard items we sell — things that aren't stock products in Inventory but that we price again and again: "Wardrobe, 2-door, 600mm deep, per linear metre", "Kitchen base unit installation", "Delivery within Abuja".</p>
<h1>A catalogue item</h1>
<ul>
<li><strong>Code</strong> — a short reference. Sales Settings can require catalogue codes to be unique, so every item can be identified without doubt.</li>
<li><strong>Description</strong> (required) — clear and specific; it appears on the client's quote.</li>
<li><strong>Default unit of measure</strong> and <strong>default unit price</strong> — filled in automatically when you add the item to a BoQ or quote, and adjustable on each job.</li>
<li><strong>Image</strong> (up to 2 MB) — a photo or render the client sees on the quote.</li>
<li><strong>Active</strong> — switch off items we no longer sell, rather than deleting them.</li>
</ul>
<h1>Why it matters</h1>
<ul>
<li>Every estimator describes and prices the same item the same way.</li>
<li>Quotes are faster, and clients see professional, consistent descriptions and images.</li>
<li>When a rate changes, update it once in the catalogue.</li>
</ul>
<blockquote><strong>Keep the catalogue clean.</strong> Search before adding an item; review default prices at least quarterly against actual job costs.</blockquote>
HTML,
                ],
                [
                    'title' => 'Quote templates',
                    'content' => <<<'HTML'
<p>Many Thekan jobs look alike: a three-bedroom apartment fit-out, a standard kitchen, an office floor. <strong>Quote Templates</strong> let you start the next similar quote from a proven structure instead of a blank page.</p>
<h1>What a template holds</h1>
<ul>
<li>A <strong>name</strong> and description.</li>
<li><strong>Sections</strong> (with codes and titles) — for example "A. Kitchen", "B. Wardrobes", "C. Delivery &amp; Installation".</li>
<li><strong>Lines</strong> in each section — catalogue items, inventory products or free text — with quantities, unit prices, discounts and tax.</li>
<li><strong>Defaults</strong> for the commercial terms: VAT rate, delivery and installation amount, upfront %, lead time and other comments.</li>
</ul>
<h1>Two ways to create one</h1>
<ol>
<li>Build it under <strong>Sales → Quote Templates</strong>.</li>
<li>Or, from any good quote, choose <strong>Save as Template</strong> and give it a name — the quote's current sections and lines are captured as a reusable template.</li>
</ol>
<p>When you start a new quote from a template, you then adjust quantities, prices and terms to the job. The template stays unchanged.</p>
<blockquote><strong>Templates protect against omissions.</strong> A template built from a well-run job already includes delivery, installation, protection and hardware — the items people forget.</blockquote>
HTML,
                ],
                [
                    'title' => 'Price lists and pricing rules',
                    'content' => <<<'HTML'
<h1>Price lists</h1>
<p><strong>Price Lists</strong> (Sales → Price Lists) hold agreed prices for inventory products:</p>
<ul>
<li>Each list has a <strong>name</strong>, optional code, <strong>currency</strong>, <strong>type</strong> (<strong>sale</strong> or <strong>purchase</strong>) and optional <strong>valid from / valid to</strong> dates.</li>
<li>Its items set a <strong>unit price</strong> per product variant, optionally from a <strong>minimum quantity</strong> — so bulk orders can have a lower price.</li>
<li>Use them for standard retail prices, trade prices, or prices agreed with a particular client for the length of a contract.</li>
</ul>
<h1>Pricing rules</h1>
<p><strong>Pricing Rules</strong> apply automatic discounts:</p>
<ul>
<li><strong>Apply on</strong>: everything, a product, a category, a customer or a price list.</li>
<li><strong>Discount</strong>: a <strong>percent</strong> or a <strong>fixed</strong> amount.</li>
<li>Optional conditions: <strong>minimum order quantity</strong> or <strong>minimum order amount</strong>, and a validity period.</li>
<li><strong>Priority</strong> decides which rule wins when more than one applies. Rules can be switched on and off.</li>
</ul>
<h1>Control</h1>
<ul>
<li>Only authorised people should create price lists and pricing rules — they change the price of every quote that uses them.</li>
<li>Always set end dates on promotions and contract prices, so they don't run on by accident.</li>
<li>Check the margin effect of a rule before you switch it on.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What is the Quote Catalog for?', 'options' => [
                    'A library of standard items we price repeatedly, with descriptions, default units, prices and images' => true,
                    'A list of customers' => false,
                    'The stock levels in the stores' => false,
                    'The company\'s chart of accounts' => false,
                ]],
                ['q' => 'A catalogue item is no longer sold. What should you do?', 'options' => [
                    'Mark it inactive, rather than deleting it' => true,
                    'Delete it and all quotes that used it' => false,
                    'Change its description to "old"' => false,
                    'Leave it active' => false,
                ]],
                ['q' => 'How can you turn a well-structured quote into a reusable starting point?', 'options' => [
                    'Use Save as Template on the quote' => true,
                    'Copy and paste it into a spreadsheet' => false,
                    'Email it to yourself' => false,
                    'It isn\'t possible' => false,
                ]],
                ['q' => 'Two pricing rules apply to the same order. What decides which one wins?', 'options' => [
                    'The rules\' priority' => true,
                    'Whichever was created last, always' => false,
                    'The client chooses' => false,
                    'Both discounts are always added together' => false,
                ]],
                ['q' => 'Select ALL the things a pricing rule can apply on.', 'options' => [
                    'A product or a category' => true,
                    'A customer' => true,
                    'A price list' => true,
                    'A chat group' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 3: Bills of Quantities',
            'lessons' => [
                [
                    'title' => 'Building a BoQ',
                    'content' => <<<'HTML'
<p>A <strong>Bill of Quantities (BoQ)</strong> lists every item of work, measured, in a structured order. Clients, quantity surveyors and government procurement often require one: it lets them compare bids line by line, value work as it's done, and price variations fairly.</p>
<h1>Creating a BoQ (Sales → Bills of Quantities)</h1>
<ul>
<li><strong>Project</strong> (required) — choose an existing project, or type a new name: the ERP then creates a <em>draft</em> project record to hold the work. Search first so you don't create a second project for the same job.</li>
<li><strong>Customer</strong> and <strong>customer site</strong> — where the work will be done.</li>
<li><strong>BoQ date</strong> and notes.</li>
</ul>
<h1>Sections and lines</h1>
<ul>
<li><strong>Sections</strong> (code, title, note) group the work — by area ("Block A — Ground floor") or by trade ("Joinery", "Finishes").</li>
<li><strong>Lines</strong> can come from an <strong>inventory product</strong>, a <strong>quote catalogue item</strong>, or free text.</li>
<li>Each line has a description, <strong>unit of measure</strong>, <strong>quantity</strong> and <strong>unit price</strong> — or is marked <strong>lump sum</strong> with a single amount, for items that can't sensibly be measured (for example "Site protection — lump sum").</li>
</ul>
<h1>Good BoQ practice</h1>
<ul>
<li>Describe each item fully enough that two contractors would price the same thing.</li>
<li>Use lump sums sparingly — they hide quantities and make variations harder to value.</li>
<li>Keep the section order the same as the drawings and the site, so everyone can follow it.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Issuing and sharing a BoQ',
                    'content' => <<<'HTML'
<h1>Draft and issued</h1>
<ul>
<li>A new BoQ is a <strong>draft</strong>: you can keep editing it.</li>
<li>When it's checked and ready to go out, select <strong>Issue</strong>. Only draft BoQs can be issued. Issuing marks the version you sent, so everyone knows which quantities the client has.</li>
</ul>
<h1>Exporting</h1>
<p>Download the BoQ as <strong>PDF</strong> to send to the client, or as <strong>Excel</strong> when a quantity surveyor or procurement officer needs to work with the figures. The export follows your sections and totals.</p>
<h1>From BoQ to quote and project</h1>
<p>The BoQ is linked to its project, so it appears on the project's <strong>Quotes &amp; BoQs</strong> tab — the site team can see exactly what was measured and priced.</p>
<h1>Before you issue — check</h1>
<ol>
<li>Every drawing and room is covered; nothing in the scope is missing.</li>
<li>Quantities have been checked by a second person.</li>
<li>Units are right (m, m², nr, item) and consistent.</li>
<li>Lump sums are justified and described.</li>
<li>Totals add up, and the customer, site and date are correct.</li>
</ol>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Why do clients and quantity surveyors ask for a Bill of Quantities?', 'options' => [
                    'To compare bids line by line, value work done and price variations fairly' => true,
                    'Because it replaces the contract' => false,
                    'To avoid paying VAT' => false,
                    'It is only for decoration' => false,
                ]],
                ['q' => 'You type a new project name into a BoQ\'s Project field. What does the ERP do?', 'options' => [
                    'Creates a draft project record to hold the work' => true,
                    'Rejects the BoQ' => false,
                    'Creates a sales invoice' => false,
                    'Nothing — the name is ignored' => false,
                ]],
                ['q' => 'Which BoQs can be issued?', 'options' => [
                    'Draft BoQs' => true,
                    'Only BoQs already issued' => false,
                    'Any BoQ, as many times as you like' => false,
                    'Only BoQs with lump sums' => false,
                ]],
                ['q' => 'When is a lump-sum line appropriate?', 'options' => [
                    'For items that can\'t sensibly be measured, such as site protection' => true,
                    'For every line, to save time' => false,
                    'To hide the quantities from the client' => false,
                    'Never' => false,
                ]],
                ['q' => 'Select ALL the ways a BoQ line can be created.', 'options' => [
                    'From an inventory product' => true,
                    'From a quote catalogue item' => true,
                    'As free text' => true,
                    'From a payroll run' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 4: Sales quotes in Thekan ERP',
            'lessons' => [
                [
                    'title' => 'Building a quote',
                    'content' => <<<'HTML'
<p>Create quotes under <strong>Sales → Sales Quotes</strong>, starting from a template where one fits.</p>
<h1>The header</h1>
<ul>
<li><strong>Name</strong> and <strong>customer</strong> (both required).</li>
<li><strong>Site</strong> — pick one of the customer's sites, or enter a new site location and state and tick to save it as a new customer site for next time.</li>
<li><strong>Project</strong> — optional; link it where the quote belongs to an existing project.</li>
<li><strong>Quote date</strong>, <strong>valid until</strong>, <strong>currency</strong> and <strong>reference</strong> (for example the client's enquiry number).</li>
</ul>
<h1>Sections and lines</h1>
<ul>
<li>Group lines into <strong>sections</strong> with codes and titles, just as in a BoQ.</li>
<li>Each line can be an inventory product, a catalogue item or free text, with a <strong>unit of measure</strong>, <strong>quantity</strong>, <strong>unit price</strong>, optional <strong>discount %</strong> and <strong>tax</strong>.</li>
<li>Add an <strong>image</strong> to a line (up to 2 MB) — clients buying furniture want to see what they're getting.</li>
</ul>
<h1>The terms</h1>
<ul>
<li><strong>VAT rate</strong>.</li>
<li><strong>Delivery &amp; installation amount</strong>.</li>
<li><strong>Upfront %</strong> — the deposit required.</li>
<li><strong>Lead time</strong>.</li>
<li><strong>Notes</strong> and <strong>other comments</strong> — inclusions, exclusions and assumptions.</li>
</ul>
<blockquote><strong>Write for the client.</strong> The quote is a sales document. Clear sections, clear descriptions, good images and plain terms win work.</blockquote>
HTML,
                ],
                [
                    'title' => 'Totals, VAT and checking the numbers',
                    'content' => <<<'HTML'
<h1>How the quote total is built</h1>
<ol>
<li><strong>Subtotal</strong> — the sum of all lines (quantity × unit price).</li>
<li>Less <strong>discounts</strong> — the line discounts.</li>
<li>= the <strong>goods subtotal</strong>.</li>
<li>Plus <strong>VAT</strong> at the quote's VAT rate on the goods subtotal.</li>
<li>Plus the <strong>delivery &amp; installation</strong> amount.</li>
<li>= the <strong>grand total</strong>.</li>
</ol>
<p><em>Example:</em> lines ₦4,000,000, discounts ₦200,000 → goods ₦3,800,000; VAT at 7.5% ₦285,000; delivery &amp; installation ₦350,000 → grand total <strong>₦4,435,000</strong>. With 60% upfront, the deposit is about ₦2,661,000.</p>
<h1>Before you send — check</h1>
<ul>
<li>Quantities match the BoQ or take-off; nothing is missing.</li>
<li>Discounts are approved and within your authority.</li>
<li>The VAT rate is right for this client.</li>
<li>Delivery and installation is included — it's often forgotten.</li>
<li>Validity, upfront %, lead time and exclusions are filled in.</li>
<li>Customer name, site and reference are correct.</li>
</ul>
<p>Download the quote as <strong>PDF</strong> to send, or <strong>Excel</strong> if the client's QS needs the figures.</p>
HTML,
                ],
                [
                    'title' => 'The quote lifecycle and follow-up',
                    'content' => <<<'HTML'
<h1>Quote statuses</h1>
<ol>
<li><strong>Draft</strong> — being prepared. Editable; it can be deleted.</li>
<li><strong>Sent</strong> — select <strong>Send</strong> when it has gone to the client.</li>
<li>Then one of:
<ul>
<li><strong>Won</strong> — select <strong>Mark Won</strong> when the client accepts.</li>
<li><strong>Rejected</strong> — the client said no.</li>
<li><strong>Expired</strong> — the validity date passed without a decision. Only sent quotes can be marked expired.</li>
</ul>
</li>
</ol>
<h1>Rules to know</h1>
<ul>
<li>Only <strong>draft</strong> or <strong>won</strong> quotes can be edited — a sent quote is the version the client has, so it is locked while they consider it.</li>
<li>Only <strong>draft</strong> quotes can be deleted. Sent, won, rejected and expired quotes stay on record.</li>
<li>If the client asks for changes after you've sent it, prepare a revised quote rather than overwriting what they were sent.</li>
</ul>
<h1>Follow-up: where sales are won</h1>
<ul>
<li>Follow up every sent quote within a few days — by phone, not just email — and log the conversation in CRM.</li>
<li>Ask what's stopping a decision: price, design, timing, budget approval?</li>
<li>Keep statuses honest. A quote left "sent" for months hides the real pipeline; mark it expired or rejected and record why.</li>
<li>When a quote is lost, record the reason. Losing on price again and again tells you something about your rates.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Lines ₦2,000,000, discounts ₦0, VAT 7.5%, delivery & installation ₦100,000. What is the grand total?', 'options' => [
                    '₦2,250,000' => true,
                    '₦2,100,000' => false,
                    '₦2,157,500' => false,
                    '₦2,000,000' => false,
                ]],
                ['q' => 'Which quotes can be edited?', 'options' => [
                    'Draft or won quotes' => true,
                    'Sent quotes only' => false,
                    'Any quote in any status' => false,
                    'Rejected quotes only' => false,
                ]],
                ['q' => 'A sent quote passes its validity date without a decision. What should happen?', 'options' => [
                    'Mark it Expired (and follow up or re-quote if the client is still interested)' => true,
                    'Delete it' => false,
                    'Leave it as Sent indefinitely' => false,
                    'Mark it Won' => false,
                ]],
                ['q' => 'The client wants changes to a quote you\'ve already sent. What is best practice?', 'options' => [
                    'Prepare a revised quote rather than overwriting what they were sent' => true,
                    'Delete the sent quote' => false,
                    'Change the PDF by hand' => false,
                    'Ignore the request' => false,
                ]],
                ['q' => 'Select ALL the things to check before sending a quote.', 'options' => [
                    'Delivery and installation is included' => true,
                    'Discounts are approved and within your authority' => true,
                    'Validity, upfront %, lead time and exclusions are filled in' => true,
                    'The quote has no sections' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 5: After the win',
            'lessons' => [
                [
                    'title' => 'Review and handover',
                    'content' => <<<'HTML'
<p>Winning the quote is the start of delivery, not the end of sales. A poor handover is where margin is lost: the project team doesn't know what was promised, and the client gets something different from what they bought.</p>
<h1>Review the won quote</h1>
<p>A won quote can be <strong>reviewed</strong> before it moves on: a reviewer records <strong>review comments</strong> on the quote — for example confirming the final scope, the agreed price and payment terms, or flagging risks. The ERP records who reviewed it and when. Reviews can be repeated if things change.</p>
<h1>A good handover covers</h1>
<ul>
<li>The final, agreed scope — with the drawings and the version of the quote the client accepted.</li>
<li>Price, payment terms, deposit received (or not), and retention.</li>
<li>Promised lead time and any fixed dates the client cares about.</li>
<li>Exclusions and assumptions — what we said we would <em>not</em> do.</li>
<li>Client contacts, decision-makers and site access details.</li>
<li>Any special risks: tight access, a demanding client, unusual materials.</li>
</ul>
<blockquote><strong>Rule:</strong> nothing should start in the workshop until the client has approved the design and the deposit has been received.</blockquote>
HTML,
                ],
                [
                    'title' => 'Sales order or project?',
                    'content' => <<<'HTML'
<p>A won quote takes one of two routes into delivery, depending on what we're selling.</p>
<h1>Convert to Sales Order — for product sales</h1>
<p>Use <strong>Convert to Sales Order</strong> when the quote sells <strong>inventory products</strong> — finished items from stock or standard products. The ERP creates a draft sales order with the quote's lines.</p>
<ul>
<li>Only <strong>won</strong> quotes can be converted.</li>
<li>Every line must be an inventory product. If the quote has catalogue or free-text lines (services, bespoke work), the ERP refuses and tells you to use <strong>Create Project</strong> instead.</li>
</ul>
<h1>Create Project — for jobs</h1>
<p>Use <strong>Create Project</strong> for bespoke furniture, installation, fit-out and construction work. The ERP:</p>
<ul>
<li>creates a <strong>planned</strong> project linked to the client and the quote;</li>
<li>creates a <strong>draft budget</strong> with a line for each quote line, grouped by the quote's sections — so the project team can track actual cost against what we sold from day one.</li>
</ul>
<p>Only won quotes can create a project, and only once per quote.</p>
<h1>Save as template</h1>
<p>If the job is one we'll sell again, <strong>Save as Template</strong> now — while the structure is fresh.</p>
<blockquote>Choosing the wrong route causes trouble later: a fit-out run as a sales order has no tasks, timesheets or cost control; a stock sale run as a project is needlessly heavy.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'A won quote has catalogue and free-text lines for bespoke joinery. Which route should it take?', 'options' => [
                    'Create Project' => true,
                    'Convert to Sales Order' => false,
                    'Delete it and start again' => false,
                    'Mark it Expired' => false,
                ]],
                ['q' => 'What does Create Project produce from a won quote?', 'options' => [
                    'A planned project linked to the client and quote, and a draft budget with a line per quote line' => true,
                    'A posted invoice' => false,
                    'A payment receipt' => false,
                    'A purchase order to every supplier' => false,
                ]],
                ['q' => 'Why might the ERP refuse to convert a won quote to a sales order?', 'options' => [
                    'Some lines are not inventory products' => true,
                    'The quote is too large' => false,
                    'The client has no email address' => false,
                    'Quotes can never be converted' => false,
                ]],
                ['q' => 'What does reviewing a won quote record?', 'options' => [
                    'Review comments, and who reviewed it and when' => true,
                    'A new price for the client' => false,
                    'A payment' => false,
                    'Nothing — it is decorative' => false,
                ]],
                ['q' => 'Select ALL the things a good sales-to-project handover covers.', 'options' => [
                    'The agreed scope and accepted quote version' => true,
                    'Payment terms and whether the deposit is received' => true,
                    'Exclusions and assumptions' => true,
                    'The estimator\'s personal opinion of the client\'s taste' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 6: Order to cash',
            'lessons' => [
                [
                    'title' => 'Sales orders and deliveries',
                    'content' => <<<'HTML'
<p>The order-to-cash cycle is: <strong>Sales Order → Delivery → Invoice → Payment</strong>, with a <strong>Credit Note</strong> when something needs reversing. Each step is a document with its own status, so nothing is lost and every naira can be traced.</p>
<h1>Sales orders</h1>
<ul>
<li>A sales order records what the customer has ordered: product variants, quantities and prices. It needs at least one valid line.</li>
<li>Statuses: <strong>draft</strong> → <strong>confirmed</strong> → <strong>closed</strong> (or <strong>cancelled</strong>).</li>
<li>Only draft orders can be edited, confirmed or deleted.</li>
<li>A confirmed order can be <strong>unconfirmed</strong> — but not once delivery notes exist for it.</li>
<li>Delivered orders can't be cancelled.</li>
<li>Orders support partial fulfilment: delivered and invoiced quantities update what's still outstanding.</li>
</ul>
<h1>Deliveries (delivery notes)</h1>
<ul>
<li>Only <strong>confirmed</strong> orders can be delivered.</li>
<li>The delivery loads the order's lines and suggests a quantity to deliver; the quantity can't exceed the stock available in the chosen store for that product variant.</li>
<li>Record the driver and dispatch details, and print the delivery note for the customer to sign.</li>
<li>Statuses: <strong>draft</strong> → <strong>posted</strong> (or <strong>cancelled</strong>). Add at least one line before posting; posted deliveries can't be edited.</li>
<li>Partial deliveries are fine — the rest stays outstanding on the order.</li>
</ul>
<blockquote><strong>Signed delivery notes are evidence.</strong> Upload them, so there's no argument later about what was delivered and when.</blockquote>
HTML,
                ],
                [
                    'title' => 'Sales invoices',
                    'content' => <<<'HTML'
<ul>
<li>Invoices are created from <strong>confirmed</strong> sales orders. The invoiced quantity can't exceed what's still outstanding on the order.</li>
<li>Invoice lines can be of four types: <strong>product</strong>, <strong>custom charge</strong>, <strong>percent charge</strong> and <strong>discount</strong>, each with its own tax selection.</li>
<li>Statuses: <strong>draft</strong> → <strong>posted</strong> (or <strong>cancelled</strong>). Only drafts can be edited, cancelled or deleted, and posting needs at least one line.</li>
<li>Print the invoice as a <strong>PDF</strong> for the customer.</li>
</ul>
<h1>What posting does</h1>
<p>Posting an invoice creates its accounting entry in Finance automatically: it <strong>debits accounts receivable</strong> (what the customer owes) with the grand total, <strong>credits sales revenue</strong>, and <strong>credits output VAT</strong> where VAT applies. Posting is refused if the invoice date falls in a <strong>closed accounting period</strong>, or if Finance hasn't set up the account mappings.</p>
<h1>Posted means final</h1>
<p>A posted invoice is locked — it's in the accounts and the customer has it. You never edit it. If it's wrong, or goods come back, issue a <strong>credit note</strong> against it.</p>
<h1>Good practice</h1>
<ul>
<li>Invoice promptly when goods are delivered or a payment stage is reached. Late invoices are late cash.</li>
<li>Clerks prepare drafts; a supervisor or finance officer posts. That separation catches mistakes before they reach the accounts.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Payments, allocation and credit notes',
                    'content' => <<<'HTML'
<h1>Recording payments</h1>
<ul>
<li>Create a payment against the customer for the <strong>amount received</strong>, with the method and bank account.</li>
<li><strong>Allocate</strong> it to one or more invoices. The total allocated can never exceed the amount received.</li>
<li>Post it: a payment needs at least one allocation before it can be posted. Posting <strong>debits the bank account</strong> and <strong>credits accounts receivable</strong> — so the bank account must be linked to its ledger account.</li>
<li>Statuses: <strong>draft</strong> → <strong>posted</strong>; only posted payments can be <strong>voided</strong>, and only drafts can be edited or deleted.</li>
<li>Print a payment receipt for the customer.</li>
</ul>
<p>Allocating payments to the right invoices is what makes each invoice's balance — and the customer's ageing — accurate.</p>
<h1>Credit notes</h1>
<ul>
<li>Use a credit note for returns, price adjustments and correcting a posted invoice.</li>
<li>Link it to the <strong>posted</strong> invoice it corrects: the ERP loads that invoice's lines, and you adjust the quantities or amounts.</li>
<li>It must have at least one line, and a total greater than zero, before it can be posted. Posted credit notes can be voided; only drafts can be edited.</li>
<li>Where goods physically come back, Inventory records the stock return; the credit note handles the money.</li>
</ul>
<blockquote><strong>Never edit or delete a posted invoice to "fix" it.</strong> A credit note keeps the audit trail intact and the accounts right.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Which sales orders can be delivered or invoiced?', 'options' => [
                    'Confirmed orders' => true,
                    'Draft orders' => false,
                    'Cancelled orders' => false,
                    'Any order' => false,
                ]],
                ['q' => 'A posted invoice has the wrong quantity. What should you do?', 'options' => [
                    'Issue a credit note linked to the invoice, then invoice correctly if needed' => true,
                    'Edit the posted invoice' => false,
                    'Delete it' => false,
                    'Change the PDF and resend it' => false,
                ]],
                ['q' => 'What does posting a sales invoice do in Finance?', 'options' => [
                    'Debits receivables, credits sales revenue and output VAT' => true,
                    'Debits the bank account' => false,
                    'Nothing — Finance is separate' => false,
                    'Pays the supplier' => false,
                ]],
                ['q' => 'A customer pays ₦1,000,000. Can you allocate ₦1,200,000 across their invoices?', 'options' => [
                    'No — the total allocated can\'t exceed the amount received' => true,
                    'Yes, the extra is a gift' => false,
                    'Yes, if the invoices are old' => false,
                    'Only on Fridays' => false,
                ]],
                ['q' => 'Select ALL the invoice line types.', 'options' => [
                    'Product' => true,
                    'Custom charge and percent charge' => true,
                    'Discount' => true,
                    'Salary' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 7: Controls, analytics and learning',
            'lessons' => [
                [
                    'title' => 'Controls: who prepares, who posts',
                    'content' => <<<'HTML'
<p>Sales documents create money owed to us and income in our accounts. That's why the ERP separates <strong>preparing</strong> from <strong>posting</strong>:</p>
<ul>
<li><strong>Draft documents are editable. Posted documents are locked.</strong> This protects reporting accuracy and prevents audit problems.</li>
<li>Only people with the <strong>post</strong> permission can post deliveries, invoices, payments and credit notes. Recommended: sales clerks prepare drafts; sales supervisors and finance officers post.</li>
<li>Every posting and void records who did it and when, and appears in the audit log.</li>
</ul>
<h1>Typical roles</h1>
<ul>
<li><strong>Estimator / sales executive</strong> — catalogue use, BoQs, quotes, follow-up, handover.</li>
<li><strong>Sales clerk</strong> — prepares orders, deliveries, invoices, payments and credit notes as drafts.</li>
<li><strong>Sales supervisor</strong> — confirms orders and posts documents after checking them.</li>
<li><strong>Finance officer</strong> — payment allocation, receivables, credit notes and reconciliation.</li>
<li><strong>Sales manager</strong> — pricing authority, discounts, analytics and performance.</li>
</ul>
<h1>Red flags to report</h1>
<ul>
<li>Discounts above someone's authority, or repeated "special prices" for the same customer.</li>
<li>Credit notes with no clear reason.</li>
<li>Payments allocated to the wrong customer's invoices.</li>
<li>Requests to post a document "just this once" without the normal checks.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Sales analytics and learning from every job',
                    'content' => <<<'HTML'
<h1>Sales Analytics</h1>
<p><strong>Sales → Sales Analytics</strong> shows, for a date range and customer:</p>
<ul>
<li><strong>Total invoiced</strong> — from posted invoices.</li>
<li><strong>Total paid</strong> — from posted payments allocated to invoices.</li>
<li><strong>Outstanding balance</strong> — invoiced minus payments and credits.</li>
<li>Top customers, sales trends, outstanding invoices and their age, and the impact of credit notes.</li>
</ul>
<p>Draft documents don't count in these figures — another reason to post promptly once documents are checked.</p>
<h1>Close the loop: estimate versus actual</h1>
<p>The best estimators improve every year because they learn from finished jobs:</p>
<ol>
<li>For jobs created from a quote, the project's budget started from the quote's lines.</li>
<li>When the job ends, compare the project's actual costs and hours (on the Project Profitability dashboard) with what was quoted.</li>
<li>Update catalogue rates, templates and waste allowances where actuals were consistently higher or lower.</li>
<li>Record why quotes were lost, and look for patterns.</li>
</ol>
<p>Mark this lesson complete, then take the final assessment: 25 questions in 40 minutes. You need 80% to pass, with up to 3 attempts.</p>
HTML,
                ],
            ],
            'final' => [
                'title' => 'Sales & Estimating — Final Assessment',
                'slug' => 'erp-sales-assessment',
                'description' => '<p>The final assessment for Sales &amp; Estimating in Thekan ERP. It covers estimating practice, commercial terms, quoting and the order-to-cash flow in the ERP.</p>',
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
                    ['q' => 'Which step of estimating should always involve a second person?', 'options' => [
                        'Review — checking quantities, rates and arithmetic before the quote goes out' => true,
                        'Typing the client\'s name' => false,
                        'Choosing the PDF colour' => false,
                        'None — estimates are personal' => false,
                    ]],
                    ['q' => 'Why add a waste allowance to sheet materials?', 'options' => [
                        'Offcuts and breakage mean more material is used than the finished item contains' => true,
                        'To increase VAT' => false,
                        'Because clients expect it to be shown separately' => false,
                        'It is not needed' => false,
                    ]],
                    ['q' => 'Cost ₦200,000, price ₦250,000. What is the margin?', 'options' => [
                        '20%' => true,
                        '25%' => false,
                        '50%' => false,
                        '80%' => false,
                    ]],
                    ['q' => 'On a 20% margin job, you give a 10% discount on price. Roughly what happens to profit?', 'options' => [
                        'It is roughly halved' => true,
                        'It falls by 10%' => false,
                        'It doesn\'t change' => false,
                        'It doubles' => false,
                    ]],
                    ['q' => 'A government client\'s tender requires a specific format and closes at noon Friday. What is the right approach?', 'options' => [
                        'Follow their procurement rules exactly and submit complete before the deadline' => true,
                        'Use our own format — it looks better' => false,
                        'Submit late with an apology' => false,
                        'Send prices only, without the required documents' => false,
                    ]],
                    ['q' => 'Select ALL the items that should be written into a quote as assumptions or exclusions where relevant.', 'options' => [
                        '"Excludes electrical and plumbing connections"' => true,
                        '"Assumes walls are plumb and plastered"' => true,
                        '"Assumes lift access to Floor 3"' => true,
                        '"Price may change whenever we like"' => false,
                    ]],
                    ['q' => 'What does Sales Settings\' unique catalogue code option do?', 'options' => [
                        'Requires every quote catalogue item to have a unique code' => true,
                        'Hides catalogue prices' => false,
                        'Deletes duplicate customers' => false,
                        'Sets the VAT rate' => false,
                    ]],
                    ['q' => 'What do quote templates hold besides sections and lines?', 'options' => [
                        'Default VAT, delivery & installation, upfront %, lead time and comments' => true,
                        'The client\'s bank details' => false,
                        'Payroll data' => false,
                        'Nothing else' => false,
                    ]],
                    ['q' => 'A price list item has a minimum quantity of 50. What does that allow?', 'options' => [
                        'A different unit price that applies from 50 units upwards' => true,
                        'Selling no fewer than 50 to anyone, ever' => false,
                        'A free item every 50' => false,
                        'Nothing' => false,
                    ]],
                    ['q' => 'Select ALL the discount types a pricing rule can use.', 'options' => [
                        'Percent' => true,
                        'Fixed amount' => true,
                        'Free delivery forever' => false,
                        'Random' => false,
                    ]],
                    ['q' => 'What is required when creating a BoQ?', 'options' => [
                        'A project, a BoQ date and at least one line' => true,
                        'A posted invoice' => false,
                        'A payment' => false,
                        'Nothing' => false,
                    ]],
                    ['q' => 'Why should you search before typing a new project name into a BoQ?', 'options' => [
                        'Typing a new name creates a draft project — you could duplicate an existing one' => true,
                        'Searching is faster to type' => false,
                        'The BoQ will be deleted otherwise' => false,
                        'There is no reason' => false,
                    ]],
                    ['q' => 'When should a BoQ be issued?', 'options' => [
                        'When it is checked and ready to go to the client; only drafts can be issued' => true,
                        'Before quantities are measured' => false,
                        'After the job is complete' => false,
                        'Never' => false,
                    ]],
                    ['q' => 'Lines ₦10,000,000, discounts ₦1,000,000, VAT 7.5%, delivery & installation ₦500,000. What is the grand total?', 'options' => [
                        '₦10,175,000' => true,
                        '₦10,250,000' => false,
                        '₦9,675,000' => false,
                        '₦11,250,000' => false,
                    ]],
                    ['q' => 'A new site for an existing customer is entered on a quote. How can it be kept for next time?', 'options' => [
                        'Tick the option to save it as a new customer site' => true,
                        'Write it in chat' => false,
                        'Create a new customer' => false,
                        'It can\'t be kept' => false,
                    ]],
                    ['q' => 'Which quotes can be deleted?', 'options' => [
                        'Only draft quotes' => true,
                        'Any quote' => false,
                        'Won quotes' => false,
                        'Rejected quotes' => false,
                    ]],
                    ['q' => 'A quote was sent three months ago and the client has gone silent. What does honest pipeline management require?', 'options' => [
                        'Follow up, and if there\'s no decision mark it Expired with the reason' => true,
                        'Leave it as Sent to keep the pipeline looking big' => false,
                        'Mark it Won' => false,
                        'Delete it' => false,
                    ]],
                    ['q' => 'A won quote sells 40 standard office chairs from stock. Which route fits?', 'options' => [
                        'Convert to Sales Order' => true,
                        'Create Project' => false,
                        'Issue a BoQ' => false,
                        'Mark it Expired' => false,
                    ]],
                    ['q' => 'Can Create Project be used twice on the same won quote?', 'options' => [
                        'No — only once per quote' => true,
                        'Yes, as often as needed' => false,
                        'Only on weekends' => false,
                        'Only for draft quotes' => false,
                    ]],
                    ['q' => 'When should workshop manufacture of bespoke furniture start?', 'options' => [
                        'After the client approves the design and the deposit is received' => true,
                        'As soon as the quote is sent' => false,
                        'Before the quote is prepared, to save time' => false,
                        'Whenever the workshop is free' => false,
                    ]],
                    ['q' => 'A confirmed sales order already has a delivery note. Can it be unconfirmed?', 'options' => [
                        'No — orders with delivery notes can\'t be unconfirmed' => true,
                        'Yes, always' => false,
                        'Yes, by deleting the delivery note in the database' => false,
                        'Only by the customer' => false,
                    ]],
                    ['q' => 'A delivery suggests 10 units but the store has 6. What can be delivered?', 'options' => [
                        'No more than the 6 available; the rest stays outstanding' => true,
                        'All 10 — stock doesn\'t matter' => false,
                        'Nothing at all' => false,
                        '16' => false,
                    ]],
                    ['q' => 'Why might posting an invoice be refused?', 'options' => [
                        'The invoice date falls in a closed accounting period, or account mappings aren\'t set' => true,
                        'The customer has paid already' => false,
                        'The invoice has a PDF' => false,
                        'Invoices are never posted' => false,
                    ]],
                    ['q' => 'What must a payment have before it can be posted?', 'options' => [
                        'At least one allocation to an invoice' => true,
                        'A credit note' => false,
                        'A delivery note' => false,
                        'Nothing' => false,
                    ]],
                    ['q' => 'Select ALL the good practices for sales controls.', 'options' => [
                        'Clerks prepare drafts; supervisors or finance post' => true,
                        'Use credit notes rather than editing posted invoices' => true,
                        'Report discounts above someone\'s authority' => true,
                        'Share one login so anyone can post' => false,
                    ]],
                ],
            ],
        ],
    ],

    // Recommended to the people who price, quote and bill work.
    'recommended_for' => ['Estimator', 'Sales Executive', 'Quantity Surveyor'],
];
