<?php

/*
 * "Procurement in Thekan ERP" — content for ThekanServicesSeeder.
 *
 * Global purchase-to-pay practice and the Procurement module feature by
 * feature: department budgets, purchase requisitions (from a supplier or
 * from stock), requests for quotation, supplier quotations, purchase
 * orders, goods receipts and the hand-off to supplier bills.
 *
 * Statuses and rules match Thekan ERP as built.
 *
 * Quiz format: 'q' => question, 'options' => [text => is_correct, ...].
 * More than one correct option makes it a "select all that apply" question.
 */

return [
    'test_prefix' => 'erp-procurement',
    'test_label' => 'Procurement',

    'course' => [
        'title' => 'Procurement in Thekan ERP',
        'slug' => 'thekan-erp-procurement',
        'description' => <<<'HTML'
<p>Materials and bought-in items are a large part of what every Thekan job costs: boards, edging, hardware, finishes, stone, glass, cement, steel, fittings, and the subcontractors who supply and fix them. Buying well — the right item, at the right price, on time, honestly — protects both our margins and our reputation.</p>
<p>This course teaches global purchase-to-pay practice and the <strong>Procurement</strong> module of Thekan ERP feature by feature: department budgets, requisitions, requests for quotation, supplier quotations, purchase orders, goods receipts, and how they lead to supplier bills and payment.</p>
<ul>
<li><strong>Who it's for:</strong> procurement officers, storekeepers who receive goods, approvers, and anyone who requests materials or services.</li>
<li><strong>Before you start:</strong> complete <em>Thekan ERP Foundations</em>. This course opens once you've passed it.</li>
<li><strong>How it works:</strong> 6 modules, each ending with a test you must pass to unlock the next. The last module ends with the final assessment.</li>
<li><strong>To complete it:</strong> finish every lesson and score at least 80% on the final assessment. You'll receive a certificate.</li>
</ul>
HTML,
        'catalog' => [
            'subtitle' => 'Buy correctly, receive correctly, record correctly — from requisition to supplier payment.',
            'category' => 'Thekan ERP',
            'level' => 'intermediate',
            'duration_minutes' => 180,
            'instructor_name' => 'Thekan Services Limited',
            'instructor_bio' => 'Prepared by Thekan for its procurement, stores and approving staff, based on recognised purchase-to-pay practice and on how Thekan ERP is built.',
            'outcomes' => [
                'Explain the purchase-to-pay cycle and the controls that protect it',
                'Raise clear requisitions — to buy from a supplier or to draw from stock',
                'Run competitive sourcing with RFQs, and compare supplier quotations fairly',
                'Create, approve and issue purchase orders within department budgets',
                'Receive goods accurately: received, accepted, rejected and damaged quantities',
                'Support three-way matching of order, receipt and supplier bill',
            ],
        ],
    ],

    'modules' => [

        // ------------------------------------------------------------------
        [
            'title' => 'Module 1: Purchase-to-pay — global best practice',
            'lessons' => [
                [
                    'title' => 'The purchase-to-pay cycle',
                    'preview' => true,
                    'content' => <<<'HTML'
<p><strong>Purchase-to-pay</strong> (P2P) is the cycle every organisation follows to buy what it needs: recognise the need, source it, commit to it, receive it, and pay for it. Professional bodies such as the Chartered Institute of Procurement &amp; Supply (<strong>CIPS</strong>) describe the same core stages worldwide.</p>
<h1>The cycle in Thekan ERP</h1>
<ol>
<li><strong>Purchase Requisition</strong> — someone records what is needed, why, when and where.</li>
<li><strong>Request for Quotation (RFQ)</strong> — procurement invites suppliers to quote.</li>
<li><strong>Supplier Quotation</strong> — each supplier's offer is recorded, compared and the best one accepted.</li>
<li><strong>Purchase Order (PO)</strong> — the formal commitment to the supplier.</li>
<li><strong>Goods Receipt (GRN)</strong> — what actually arrived, checked and recorded into stock.</li>
<li><strong>Supplier Bill</strong> — the supplier's invoice, matched to the PO and GRN.</li>
<li><strong>Payment</strong>.</li>
</ol>
<h1>The principles behind it</h1>
<ul>
<li><strong>Value for money</strong> — not always the lowest price, but the best balance of price, quality, delivery and service.</li>
<li><strong>Competition</strong> — get more than one quote for anything significant.</li>
<li><strong>Transparency</strong> — decisions are recorded and could be explained to an auditor or a client.</li>
<li><strong>Segregation of duties</strong> — the person who requests is not the person who approves, and the person who receives is not the person who pays.</li>
<li><strong>No PO, no pay</strong> — we only pay for what we ordered and received.</li>
</ul>
<blockquote><strong>Buy correctly, receive correctly, record correctly.</strong> That's the whole job in six words.</blockquote>
HTML,
                ],
                [
                    'title' => 'Roles, ethics and supplier relationships',
                    'content' => <<<'HTML'
<h1>Who does what</h1>
<ul>
<li><strong>Requester</strong> — raises requisitions and justifies the need (project managers, site supervisors, the workshop, offices).</li>
<li><strong>Approver</strong> — approves requisitions, quotations and purchase orders within their authority.</li>
<li><strong>Procurement officer</strong> — runs sourcing, compares quotations, creates POs and chases suppliers.</li>
<li><strong>Store or receiving officer</strong> — receives goods, checks them and posts the goods receipt.</li>
<li><strong>Finance (accounts payable)</strong> — matches supplier bills to POs and GRNs, and pays.</li>
</ul>
<h1>Ethics: non-negotiable</h1>
<ul>
<li><strong>Declare conflicts of interest</strong> — if a supplier is a relative, friend or a business you have an interest in, say so and step back from the decision.</li>
<li><strong>No gifts or favours</strong> that could influence a decision. If in doubt, decline and report it.</li>
<li><strong>Treat suppliers fairly</strong> — same information, same deadline, no sharing one supplier's prices with another.</li>
<li><strong>No splitting orders</strong> to avoid an approval limit or a competitive quote.</li>
</ul>
<h1>Supplier relationships</h1>
<ul>
<li>Keep supplier records accurate: contacts, addresses, payment terms, bank details (changed only after verification).</li>
<li>Review key suppliers on quality, delivery and price — the supplier analytics and returns history help.</li>
<li>Pay on time as agreed. Good suppliers prioritise customers who pay reliably.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What is the correct order of the purchase-to-pay cycle?', 'options' => [
                    'Requisition → RFQ → Quotation → Purchase Order → Goods Receipt → Bill → Payment' => true,
                    'Payment → Purchase Order → Requisition → Goods Receipt' => false,
                    'Goods Receipt → Requisition → Payment → Purchase Order' => false,
                    'Bill → Payment → Requisition' => false,
                ]],
                ['q' => 'What does "value for money" mean in procurement?', 'options' => [
                    'The best balance of price, quality, delivery and service — not always the lowest price' => true,
                    'Always the cheapest supplier' => false,
                    'Always the most expensive supplier' => false,
                    'Whatever the requester prefers' => false,
                ]],
                ['q' => 'Your cousin owns one of the suppliers quoting for a job. What should you do?', 'options' => [
                    'Declare the conflict of interest and step back from the decision' => true,
                    'Choose your cousin quietly' => false,
                    'Share the other quotes with your cousin' => false,
                    'Say nothing' => false,
                ]],
                ['q' => 'Why is splitting one large purchase into several small orders wrong?', 'options' => [
                    'It avoids approval limits and competitive quotes that should apply' => true,
                    'It makes more paperwork' => false,
                    'Suppliers dislike it' => false,
                    'It isn\'t wrong' => false,
                ]],
                ['q' => 'Select ALL the examples of segregation of duties in procurement.', 'options' => [
                    'The requester is not the approver' => true,
                    'The person who receives goods is not the person who pays' => true,
                    'Finance matches bills to POs and goods receipts' => true,
                    'One person requests, approves, receives and pays' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 2: Requisitions and department budgets',
            'lessons' => [
                [
                    'title' => 'Raising a purchase requisition',
                    'content' => <<<'HTML'
<p>A <strong>purchase requisition</strong> is the formal request for something the business needs. Create it under <strong>Procurement → Purchase Requisitions</strong>.</p>
<h1>The header</h1>
<ul>
<li><strong>Requisition date</strong> and <strong>needed-by date</strong> — be realistic and include supplier lead time.</li>
<li><strong>Priority</strong> — <strong>low</strong>, <strong>normal</strong>, <strong>high</strong> or <strong>urgent</strong>. "Urgent" should be rare; planning ahead is cheaper.</li>
<li><strong>Department</strong> — whose budget it comes from.</li>
<li><strong>Fulfilment type</strong> — <strong>from supplier</strong> (buy it) or <strong>from stock</strong> (draw it from one of our stores, with a source store).</li>
<li>Reference and notes — the justification: what it's for and why it's needed.</li>
</ul>
<h1>The lines</h1>
<ul>
<li>The <strong>product</strong> and, ideally, the exact <strong>variant</strong> (size, colour, finish, thickness) — or a clear description.</li>
<li><strong>Unit</strong>, <strong>quantity</strong> and <strong>estimated unit cost</strong> (both required).</li>
<li>Tax, the delivery <strong>location and store</strong>, and the <strong>project</strong> and <strong>project component</strong> it's for — so the cost lands on the right job.</li>
</ul>
<h1>Statuses</h1>
<ol>
<li><strong>Draft</strong> — editable and deletable.</li>
<li><strong>Submitted</strong> — sent for approval (only drafts can be submitted).</li>
<li><strong>Approved</strong> or <strong>rejected</strong>. A rejected requisition can be edited and resubmitted.</li>
<li><strong>Cancelled</strong> — draft, submitted or approved requisitions can be cancelled if no longer needed.</li>
</ol>
<p>You can print a requisition as PDF or export it to Excel.</p>
HTML,
                ],
                [
                    'title' => 'From stock or from a supplier?',
                    'content' => <<<'HTML'
<h1>Check stock first</h1>
<p>Before buying, check whether we already have it. Use <strong>Stock Lookup</strong> or <strong>Stock Levels</strong>. Buying what's already on a shelf ties up cash and leaves old stock to age.</p>
<h1>Fulfilling from stock</h1>
<p>If the requisition's fulfilment type is <strong>from stock</strong>, once it's <strong>approved</strong> the stores can fulfil it directly. The ERP creates the stock issue from the source store.</p>
<ul>
<li>Every line needs a specific <strong>product variant</strong> — the store must know exactly which item to issue.</li>
<li>A source store must be set.</li>
<li>A requisition can be fulfilled from stock only once.</li>
</ul>
<h1>Buying from a supplier</h1>
<p>An approved <strong>from supplier</strong> requisition moves to sourcing: procurement converts it to an RFQ (or, for a simple repeat purchase within policy, raises a PO directly).</p>
<h1>Writing a good requisition</h1>
<ul>
<li>Specify exactly: "18mm MR MDF, 2440×1220, 40 sheets" — not "boards".</li>
<li>Give the real needed-by date, and link the project and component.</li>
<li>Attach the drawing, specification or sample photo where it helps.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Department budgets',
                    'content' => <<<'HTML'
<p><strong>Department Budgets</strong> (Procurement → Department Budgets) set how much each department may commit on purchase orders in a fiscal year: a <strong>department</strong>, a <strong>fiscal year</strong>, an <strong>amount</strong> and notes. The budget status shows <strong>allocated</strong>, <strong>used</strong> and <strong>remaining</strong>.</p>
<h1>How the ERP enforces it</h1>
<ul>
<li>A purchase order is attributed to a department through the requisition it came from.</li>
<li>"Used" is the total of that department's purchase orders for the year that are past draft and not cancelled.</li>
<li>When someone <strong>approves</strong> a PO, the ERP checks it against the remaining budget. If approving it would exceed the budget, approval is <strong>refused</strong>, with the allocated, used and remaining amounts shown.</li>
<li>If no budget is set for that department and year, the check doesn't apply.</li>
</ul>
<h1>When the budget is exhausted</h1>
<p>Don't look for ways around it. Raise it with the department head: either the purchase waits, the budget is formally increased, or spending is reprioritised. That's exactly the conversation the control is designed to trigger.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Which requisitions can be edited?', 'options' => [
                    'Draft or rejected requisitions' => true,
                    'Approved requisitions' => false,
                    'Cancelled requisitions' => false,
                    'Any requisition' => false,
                ]],
                ['q' => 'What must every line have before a requisition can be fulfilled from stock?', 'options' => [
                    'A specific product variant' => true,
                    'A supplier' => false,
                    'A purchase order' => false,
                    'An invoice' => false,
                ]],
                ['q' => 'A PO approval is refused with a budget message. Why?', 'options' => [
                    'Approving it would exceed the department\'s remaining budget for the year' => true,
                    'The supplier is inactive' => false,
                    'The PO has a PDF' => false,
                    'Approvals are disabled on Mondays' => false,
                ]],
                ['q' => 'Which is the best requisition line?', 'options' => [
                    '"18mm MR MDF, 2440×1220, 40 sheets — Block B kitchens"' => true,
                    '"Boards"' => false,
                    '"Materials for site"' => false,
                    '"Same as last time"' => false,
                ]],
                ['q' => 'Select ALL the requisition priorities.', 'options' => [
                    'Low and normal' => true,
                    'High' => true,
                    'Urgent' => true,
                    'Whenever' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 3: Sourcing — RFQs and supplier quotations',
            'lessons' => [
                [
                    'title' => 'Requests for quotation',
                    'content' => <<<'HTML'
<p>An <strong>RFQ</strong> invites suppliers to quote for the same requirement on the same terms. Create one from an <strong>approved</strong> requisition (so its lines carry over), or directly.</p>
<h1>An RFQ</h1>
<ul>
<li><strong>RFQ date</strong>, <strong>closing date</strong>, currency and exchange rate, reference and notes.</li>
<li><strong>Lines</strong> — item, description, unit, quantity, estimated unit cost, tax, delivery location and store.</li>
<li><strong>Suppliers</strong> — at least one; for anything significant, at least three. Record the contact person and their email and phone.</li>
</ul>
<h1>Statuses</h1>
<ol>
<li><strong>Draft</strong> — editable and deletable.</li>
<li><strong>Sent</strong> — select <strong>Send</strong> once suppliers have it; you must have added at least one supplier.</li>
<li><strong>Closed</strong> — the closing date has passed; no more quotes accepted.</li>
<li><strong>Awarded</strong> — a sent or closed RFQ is awarded once the winning quotation is chosen.</li>
<li><strong>Cancelled</strong> — if the need goes away.</li>
</ol>
<p>Print the RFQ as PDF to send to suppliers. RFQs past their closing date appear on the dashboard so they don't sit forgotten.</p>
<h1>Good practice</h1>
<ul>
<li>Give every supplier the same specification, quantities, delivery requirements and deadline.</li>
<li>Ask for lead time, validity, payment terms and delivery cost, not just unit price.</li>
<li>Don't reveal one supplier's price to another.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Recording and comparing quotations',
                    'content' => <<<'HTML'
<h1>Recording a supplier quotation</h1>
<p>For each response, create a <strong>Supplier Quotation</strong> from the RFQ: the supplier, their quote number, quotation date, valid-until date, currency and reference. For each line record the <strong>quantity</strong>, <strong>unit price</strong>, <strong>discount %</strong>, <strong>tax</strong>, <strong>lead time in days</strong> and remarks.</p>
<h1>The quotation workflow</h1>
<ol>
<li><strong>Draft</strong> — being entered.</li>
<li><strong>Submitted</strong> — ready for review.</li>
<li><strong>Reviewed</strong> — checked and compared.</li>
<li><strong>Accepted</strong> or <strong>rejected</strong>. A rejected quotation can be corrected and resubmitted.</li>
</ol>
<h1>Comparing fairly</h1>
<ul>
<li>Compare the <strong>total delivered cost</strong>: price after discount, plus tax and delivery.</li>
<li>Weigh <strong>lead time</strong> against the needed-by date — the cheapest supplier is no good if the kitchen arrives after the client moves in.</li>
<li>Consider <strong>quality and reliability</strong> — past returns, defects and late deliveries.</li>
<li>Check that each quote matches the specification. A cheaper quote for a lower grade isn't a like-for-like comparison.</li>
<li>Record why the winner was chosen, especially if it isn't the cheapest.</li>
</ul>
<p>The accepted quotation can then be converted into a purchase order, carrying its supplier, prices and lines across.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What must an RFQ have before it can be sent?', 'options' => [
                    'At least one supplier' => true,
                    'A goods receipt' => false,
                    'A paid invoice' => false,
                    'Nothing' => false,
                ]],
                ['q' => 'Which RFQs can be awarded?', 'options' => [
                    'Sent or closed RFQs' => true,
                    'Draft RFQs' => false,
                    'Cancelled RFQs' => false,
                    'Any RFQ' => false,
                ]],
                ['q' => 'Supplier A is 5% cheaper but quotes six weeks\' lead time; the job needs the items in three. What should drive the decision?', 'options' => [
                    'Total value — price, lead time against the need date, quality and reliability' => true,
                    'Always the cheapest price' => false,
                    'Whichever supplier calls first' => false,
                    'The longest lead time' => false,
                ]],
                ['q' => 'What is the supplier quotation workflow?', 'options' => [
                    'Draft → Submitted → Reviewed → Accepted or Rejected' => true,
                    'Draft → Posted → Void' => false,
                    'Open → Pending → Closed' => false,
                    'New → Won → Lost' => false,
                ]],
                ['q' => 'Select ALL the things to record on each quotation line.', 'options' => [
                    'Unit price and discount %' => true,
                    'Tax' => true,
                    'Lead time in days' => true,
                    'Another supplier\'s price' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 4: Purchase orders',
            'lessons' => [
                [
                    'title' => 'Creating a purchase order',
                    'content' => <<<'HTML'
<p>The <strong>purchase order</strong> is the company's formal, legally significant commitment to buy. It's the reference for receiving and for paying. Create it from an accepted supplier quotation, or directly.</p>
<h1>The header</h1>
<ul>
<li><strong>Supplier</strong> (required) and contact, and the supplier's own PO reference if they use one.</li>
<li><strong>PO date</strong> (required) and <strong>expected delivery date</strong>.</li>
<li>Currency and exchange rate.</li>
<li><strong>Delivery location and store</strong> — where the goods must go. Getting this wrong is a common cause of "missing" stock.</li>
<li><strong>Bill-to location</strong>, <strong>payment terms</strong>, <strong>incoterms</strong> (for imports), reference.</li>
<li><strong>Notes</strong> (printed for the supplier) and <strong>internal notes</strong> (not printed).</li>
</ul>
<h1>The lines</h1>
<p>Each line carries the item, description, unit, quantity, price and tax — linked back to the requisition, RFQ and quotation lines where it came from — plus the delivery location and store, and the <strong>project</strong> it's for.</p>
<h1>Before you approve</h1>
<ul>
<li>Price and terms match the accepted quotation.</li>
<li>Quantities match what was approved on the requisition.</li>
<li>Delivery date, store and project are right.</li>
<li>It's within the department budget.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'The purchase order lifecycle',
                    'content' => <<<'HTML'
<h1>Statuses</h1>
<ol>
<li><strong>Draft</strong> — editable; only drafts can be deleted.</li>
<li><strong>Approved</strong> — authorised (subject to the department budget check). Draft and approved POs can still be edited.</li>
<li><strong>Issued</strong> — sent to the supplier. Only approved POs can be issued. Print the PO as PDF to send.</li>
<li><strong>Partially received</strong> / <strong>fully received</strong> — updated automatically as goods receipts are posted.</li>
<li><strong>Partially billed</strong> / <strong>billed</strong> — as supplier bills are recorded.</li>
<li><strong>Closed</strong> — finished. Issued, received or billed POs can be closed.</li>
<li><strong>Cancelled</strong> — only draft or approved POs (not yet issued) can be cancelled.</li>
</ol>
<h1>Chasing delivery</h1>
<p>POs overdue for delivery appear on the dashboard. Chase suppliers <strong>before</strong> the expected date for critical items, and tell the project manager straight away if a delivery will be late — it may change the site programme.</p>
<h1>Changes after issue</h1>
<p>Once a PO is issued, the supplier is working to it. Any change — quantity, price, date — must be agreed with the supplier in writing and recorded. Don't let the receipt quietly differ from the order.</p>
<blockquote><strong>No PO, no purchase.</strong> Committing to suppliers verbally, without a PO in the ERP, is how firms end up paying for things nobody approved.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Which purchase orders can be issued to the supplier?', 'options' => [
                    'Approved POs' => true,
                    'Draft POs' => false,
                    'Cancelled POs' => false,
                    'Closed POs' => false,
                ]],
                ['q' => 'A PO has already been issued and the need disappears. Can it simply be cancelled in the ERP?', 'options' => [
                    'No — only draft or approved POs can be cancelled; agree with the supplier and close it properly' => true,
                    'Yes, always' => false,
                    'Yes, by deleting it' => false,
                    'Only by the supplier' => false,
                ]],
                ['q' => 'What updates a PO to "partially received"?', 'options' => [
                    'Posting a goods receipt for part of the order' => true,
                    'Printing the PDF' => false,
                    'Calling the supplier' => false,
                    'Approving the requisition' => false,
                ]],
                ['q' => 'What is the difference between PO notes and internal notes?', 'options' => [
                    'Notes are printed for the supplier; internal notes are not' => true,
                    'There is no difference' => false,
                    'Internal notes are emailed to the supplier' => false,
                    'Notes are only for finance' => false,
                ]],
                ['q' => 'Select ALL the checks before approving a PO.', 'options' => [
                    'Price and terms match the accepted quotation' => true,
                    'Quantities match the approved requisition' => true,
                    'Delivery store and project are correct' => true,
                    'The supplier is a friend' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 5: Receiving goods',
            'lessons' => [
                [
                    'title' => 'Creating a goods receipt',
                    'content' => <<<'HTML'
<p>A <strong>Goods Receipt (GRN)</strong> records what physically arrived against a purchase order. Create it from the PO so the lines load automatically.</p>
<h1>Before you record anything</h1>
<ul>
<li>Count and inspect the delivery <strong>against the supplier's delivery note and the PO</strong> — not against the delivery note alone.</li>
<li>Check condition, dimensions, colour and finish, grade and quantities.</li>
<li>Note the supplier's delivery note number.</li>
</ul>
<h1>For each line</h1>
<ul>
<li><strong>Product variant</strong> (required) — the exact item received. A GRN can't be approved or posted while any line is missing its variant.</li>
<li><strong>Ordered</strong> and <strong>previously received</strong> quantities are shown.</li>
<li><strong>Received quantity</strong> — what arrived. It can't exceed what's still outstanding on the PO.</li>
<li>Then split it: <strong>accepted</strong>, <strong>rejected</strong> and <strong>damaged</strong>. Accepted + rejected + damaged can't exceed received.</li>
<li><strong>Unit cost</strong>, <strong>batch</strong>, <strong>serial number</strong>, <strong>expiry date</strong> (for adhesives, finishes and other dated items) and remarks.</li>
</ul>
<h1>Delivery store</h1>
<p>Set the <strong>delivery store</strong> — where the stock goes. A GRN can't be posted without one.</p>
<blockquote><strong>Only accepted quantities go into stock.</strong> Recording damaged boards as accepted overstates stock and means paying for goods we can't use.</blockquote>
HTML,
                ],
                [
                    'title' => 'Approving and posting the receipt',
                    'content' => <<<'HTML'
<h1>Statuses</h1>
<ol>
<li><strong>Draft</strong> — being recorded; editable and deletable.</li>
<li><strong>Approved</strong> — checked and authorised (only drafts can be approved).</li>
<li><strong>Posted</strong> — final (only approved receipts can be posted).</li>
<li><strong>Cancelled</strong> — draft or approved receipts can be cancelled.</li>
</ol>
<p>The receipt's status also shows whether the PO is <strong>partially</strong> or <strong>completely</strong> received.</p>
<h1>What posting does</h1>
<ul>
<li>Creates the <strong>stock entry</strong> that puts the accepted quantities into the delivery store.</li>
<li>Updates the PO's received quantities and status.</li>
<li>Makes the receipt available for Finance to match the supplier's bill against.</li>
</ul>
<h1>The golden rule</h1>
<p><strong>Never post a GRN until the store, the quantities and the variants are confirmed.</strong> A wrong posted receipt puts wrong stock in the wrong place and has to be corrected through returns and adjustments.</p>
<h1>Rejected and damaged goods</h1>
<ul>
<li>Tell the supplier the same day, with photos.</li>
<li>Agree replacement or credit, and record the return to the supplier through Inventory → Supplier Returns.</li>
<li>Tell the project manager if the shortfall affects the programme.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => '20 sheets were received: 17 good, 2 rejected for wrong colour, 1 damaged. What goes into stock?', 'options' => [
                    '17 — only the accepted quantity' => true,
                    '20' => false,
                    '18' => false,
                    '0 until the supplier is paid' => false,
                ]],
                ['q' => 'Why can\'t a GRN be approved?', 'options' => [
                    'One or more received lines are missing product variants' => true,
                    'It has a supplier delivery note number' => false,
                    'It has remarks' => false,
                    'The supplier is local' => false,
                ]],
                ['q' => 'Which goods receipts can be posted?', 'options' => [
                    'Approved receipts' => true,
                    'Draft receipts' => false,
                    'Cancelled receipts' => false,
                    'Any receipt' => false,
                ]],
                ['q' => 'What should you check deliveries against?', 'options' => [
                    'The PO as well as the supplier\'s delivery note' => true,
                    'Only the supplier\'s delivery note' => false,
                    'Nothing — trust the driver' => false,
                    'Last month\'s order' => false,
                ]],
                ['q' => 'Select ALL the things posting a goods receipt does.', 'options' => [
                    'Puts accepted quantities into the delivery store' => true,
                    'Updates the PO\'s received quantities and status' => true,
                    'Makes the receipt available for bill matching' => true,
                    'Pays the supplier' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 6: Bills, matching and control',
            'lessons' => [
                [
                    'title' => 'Three-way matching and supplier bills',
                    'content' => <<<'HTML'
<p><strong>Three-way matching</strong> is the standard control before paying a supplier: the <strong>purchase order</strong> (what we agreed), the <strong>goods receipt</strong> (what we received and accepted) and the <strong>supplier's bill</strong> (what they're charging) must agree.</p>
<ul>
<li>Quantities billed ≤ quantities accepted.</li>
<li>Prices billed = prices on the PO.</li>
<li>Tax and charges as agreed.</li>
</ul>
<p>In Finance, supplier bills can be loaded <strong>from a purchase order</strong> or <strong>from a goods receipt</strong>, so their lines link back to the PO and GRN lines. That link is what makes matching — and any query — quick.</p>
<h1>When they don't match</h1>
<ul>
<li>Billed more than received: hold the bill and ask the supplier for a corrected invoice or credit note.</li>
<li>Price differs from the PO: check whether a change was agreed in writing; if not, query it.</li>
<li>Goods received but no PO: escalate — this is exactly the "no PO, no pay" breach the process exists to prevent.</li>
</ul>
<h1>Supplier returns and credits</h1>
<p>Goods sent back are recorded in Inventory as a supplier return; the money side is a supplier credit in Finance. Supplier returns and their analytics show which suppliers cause the most problems.</p>
HTML,
                ],
                [
                    'title' => 'Controls, red flags and good habits',
                    'content' => <<<'HTML'
<h1>The controls in the ERP</h1>
<ul>
<li>Each step has its own permission — create, approve, issue, post, cancel. A missing button usually means a missing permission, not a fault.</li>
<li>Department budgets are checked when POs are approved.</li>
<li>Every approval, posting and cancellation is in the audit log.</li>
</ul>
<h1>Red flags to report</h1>
<ul>
<li>Orders split to stay under approval limits.</li>
<li>The same supplier always winning without competition.</li>
<li>Goods "received" that nobody on site has seen.</li>
<li>Bank detail changes requested by email.</li>
<li>Pressure to post receipts or approve orders "just this once" without checks.</li>
</ul>
<h1>Habits of a good procurement team</h1>
<ul>
<li>Plan purchases from the project programme and the Low Stock list, not in panic.</li>
<li>Keep the PO pipeline clean — close finished POs, cancel ones no longer needed.</li>
<li>Chase overdue deliveries early.</li>
<li>Review supplier performance regularly.</li>
</ul>
<p>Mark this lesson complete, then take the final assessment: 20 questions in 30 minutes. You need 80% to pass, with up to 3 attempts.</p>
HTML,
                ],
            ],
            'final' => [
                'title' => 'Procurement — Final Assessment',
                'slug' => 'erp-procurement-assessment',
                'description' => '<p>The final assessment for Procurement in Thekan ERP, covering purchase-to-pay practice and every step in the ERP.</p>',
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
                    ['q' => 'What does "no PO, no pay" mean?', 'options' => [
                        'We only pay for what was ordered on an approved PO and received' => true,
                        'Suppliers must pay us first' => false,
                        'POs are optional for small items' => false,
                        'Payment is made before the PO' => false,
                    ]],
                    ['q' => 'A supplier offers you a valuable gift while their quote is being evaluated. What should you do?', 'options' => [
                        'Decline and report it' => true,
                        'Accept it quietly' => false,
                        'Accept it and choose another supplier' => false,
                        'Share it with the team' => false,
                    ]],
                    ['q' => 'Before raising a requisition to buy, what should you check?', 'options' => [
                        'Whether we already have it in stock' => true,
                        'Nothing' => false,
                        'The weather' => false,
                        'Last year\'s payroll' => false,
                    ]],
                    ['q' => 'Which requisitions can be submitted?', 'options' => [
                        'Draft requisitions' => true,
                        'Approved requisitions' => false,
                        'Cancelled requisitions' => false,
                        'Any requisition' => false,
                    ]],
                    ['q' => 'A requisition is marked "from stock". What happens once it\'s approved?', 'options' => [
                        'The stores can fulfil it directly, creating a stock issue from the source store' => true,
                        'It is sent to suppliers' => false,
                        'It becomes a sales order' => false,
                        'Nothing — it must be re-entered as a PO' => false,
                    ]],
                    ['q' => 'Why link the project and component on requisition and PO lines?', 'options' => [
                        'So the cost lands on the right job and part of the job' => true,
                        'It makes the PDF longer' => false,
                        'Suppliers require it' => false,
                        'It isn\'t needed' => false,
                    ]],
                    ['q' => 'No department budget is set for the year. What happens when a PO is approved?', 'options' => [
                        'The budget check doesn\'t apply' => true,
                        'Approval is always refused' => false,
                        'The PO is cancelled' => false,
                        'The PO is sent to finance for payment' => false,
                    ]],
                    ['q' => 'For a significant purchase, how many suppliers should usually be invited to quote?', 'options' => [
                        'At least three' => true,
                        'Only one, the usual supplier' => false,
                        'None' => false,
                        'Exactly two' => false,
                    ]],
                    ['q' => 'A supplier calls asking what the cheapest competing price was. What do you say?', 'options' => [
                        'That you can\'t share other suppliers\' prices' => true,
                        'Give them the price' => false,
                        'Give them a slightly higher figure' => false,
                        'Email them all the quotes' => false,
                    ]],
                    ['q' => 'A quote is cheaper because it\'s for a lower grade of board. How should it be compared?', 'options' => [
                        'It isn\'t like-for-like — compare only quotes that meet the specification' => true,
                        'Choose it — cheaper is better' => false,
                        'Average it with the others' => false,
                        'Ignore the specification' => false,
                    ]],
                    ['q' => 'Which POs can be cancelled?', 'options' => [
                        'Draft or approved POs' => true,
                        'Issued and fully received POs' => false,
                        'Closed POs' => false,
                        'Any PO' => false,
                    ]],
                    ['q' => 'A supplier will deliver the kitchen two weeks late. Who must you tell straight away?', 'options' => [
                        'The project manager — it may change the site programme' => true,
                        'Nobody' => false,
                        'Only the supplier\'s driver' => false,
                        'The client\'s neighbours' => false,
                    ]],
                    ['q' => 'A PO is for 50 units and 30 have been received. Can you record 25 on the next receipt?', 'options' => [
                        'No — received quantity can\'t exceed the 20 still outstanding' => true,
                        'Yes, any amount is fine' => false,
                        'Yes, if the driver agrees' => false,
                        'Only if the PO is closed' => false,
                    ]],
                    ['q' => 'Received 10; accepted 8, rejected 1, damaged 2. What will the ERP say?', 'options' => [
                        'Accepted + rejected + damaged can\'t exceed received' => true,
                        'Nothing — it is valid' => false,
                        'The receipt is posted' => false,
                        'The PO is closed' => false,
                    ]],
                    ['q' => 'What must be set before a goods receipt can be posted?', 'options' => [
                        'The delivery store' => true,
                        'The supplier\'s bank details' => false,
                        'A sales invoice' => false,
                        'Nothing' => false,
                    ]],
                    ['q' => 'What is the golden rule of goods receipts?', 'options' => [
                        'Never post until the store, quantities and variants are confirmed' => true,
                        'Post first, check later' => false,
                        'Let the supplier post it' => false,
                        'Never record damaged goods' => false,
                    ]],
                    ['q' => 'Select ALL the documents in three-way matching.', 'options' => [
                        'Purchase order' => true,
                        'Goods receipt' => true,
                        'Supplier bill' => true,
                        'Sales quote' => false,
                    ]],
                    ['q' => 'The supplier bills 40 units but only 35 were accepted. What should happen?', 'options' => [
                        'Hold the bill and ask for a corrected invoice or credit note' => true,
                        'Pay all 40 to keep the supplier happy' => false,
                        'Change the goods receipt to 40' => false,
                        'Pay 40 and forget it' => false,
                    ]],
                    ['q' => 'A button to post a GRN is missing for you. What is the most likely reason?', 'options' => [
                        'You don\'t have the post permission' => true,
                        'The system is broken' => false,
                        'The supplier blocked it' => false,
                        'It\'s the weekend' => false,
                    ]],
                    ['q' => 'Select ALL the procurement red flags.', 'options' => [
                        'Orders split to stay under approval limits' => true,
                        'The same supplier always winning without competition' => true,
                        'Bank detail changes requested by email' => true,
                        'Three competitive quotes recorded with reasons' => false,
                    ]],
                ],
            ],
        ],
    ],

    'recommended_for' => ['Procurement Officer', 'Storekeeper', 'Accountant', 'Project Manager'],
];
