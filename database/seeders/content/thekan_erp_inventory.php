<?php

/*
 * "Inventory & Stores in Thekan ERP" — content for ThekanServicesSeeder.
 *
 * Global stock-control practice and the Inventory module feature by
 * feature: products and variants, stores and shelves, stock entries,
 * issues (including tool loans and acknowledgement), transfers, returns,
 * adjustments, levels, low stock, ageing and the transaction ledger.
 *
 * Statuses and rules match Thekan ERP as built.
 *
 * Quiz format: 'q' => question, 'options' => [text => is_correct, ...].
 * More than one correct option makes it a "select all that apply" question.
 */

return [
    'test_prefix' => 'erp-inventory',
    'test_label' => 'Inventory',

    'course' => [
        'title' => 'Inventory & Stores in Thekan ERP',
        'slug' => 'thekan-erp-inventory',
        'description' => <<<'HTML'
<p>Thekan's stores hold the boards, edging, hardware, finishes, fixings, tools and building materials our workshop and sites depend on. When stock records are right, the workshop never stops for a missing hinge and we never buy what's already on a shelf. When they're wrong, everything downstream — job costs, purchasing, production plans — is wrong too.</p>
<p>This course teaches global stock-control practice and the <strong>Inventory</strong> module of Thekan ERP feature by feature: products and variants, stores and shelves, stock entries, issues and tool loans, transfers, returns, adjustments, and the levels, low-stock and ageing reports that keep stock under control.</p>
<ul>
<li><strong>Who it's for:</strong> storekeepers and stores assistants, procurement officers, production supervisors and anyone who draws or moves stock.</li>
<li><strong>Before you start:</strong> complete <em>Thekan ERP Foundations</em>. This course opens once you've passed it.</li>
<li><strong>How it works:</strong> 6 modules, each ending with a test you must pass to unlock the next. The last module ends with the final assessment.</li>
<li><strong>To complete it:</strong> finish every lesson and score at least 80% on the final assessment. You'll receive a certificate.</li>
</ul>
HTML,
        'catalog' => [
            'subtitle' => 'Keep every board, fitting and tool accounted for — in the right store, on the right shelf.',
            'category' => 'Thekan ERP',
            'level' => 'beginner',
            'duration_minutes' => 180,
            'instructor_name' => 'Thekan Services Limited',
            'instructor_bio' => 'Prepared by Thekan for its stores, procurement and workshop teams, based on recognised stock-control practice and on how Thekan ERP is built.',
            'outcomes' => [
                'Apply the principles of accurate stock control, FIFO and cycle counting',
                'Set up products and variants so stock is tracked at the right level',
                'Record stock coming in, going out and moving between stores — with the right status flow',
                'Run tool loans and signed acknowledgements for issued stock',
                'Investigate and correct stock differences through the transaction ledger',
                'Use low stock and ageing to plan purchasing and reduce dead stock',
            ],
        ],
    ],

    'modules' => [

        // ------------------------------------------------------------------
        [
            'title' => 'Module 1: Stock control — global best practice',
            'lessons' => [
                [
                    'title' => 'Why stock accuracy matters',
                    'preview' => true,
                    'content' => <<<'HTML'
<p>Stock is money sitting on shelves. For a firm that manufactures and installs, it's also the difference between a workshop that runs and one that waits.</p>
<h1>What inaccurate stock costs</h1>
<ul>
<li><strong>Stoppages</strong> — the system says we have 200 hinges; the shelf has 20. Production stops.</li>
<li><strong>Waste</strong> — we buy what we already have, and old stock ages, warps or expires.</li>
<li><strong>Wrong job costs</strong> — materials issued to the wrong job, or not recorded at all, make jobs look more or less profitable than they are.</li>
<li><strong>Loss and theft</strong> — if nobody would notice something missing, it eventually goes missing.</li>
</ul>
<h1>What well-run stores do</h1>
<ul>
<li><strong>Every movement is recorded</strong> — in, out, between stores, adjusted. No exceptions.</li>
<li><strong>Everything has a place</strong> — a store and a shelf — and is in it.</li>
<li><strong>First in, first out (FIFO)</strong> — older stock is used first. For dated items such as adhesives, lacquers and sealants, <strong>first expired, first out (FEFO)</strong>.</li>
<li><strong>Counts are routine</strong> — not just once a year. <strong>Cycle counting</strong> checks a few items every week, with the most valuable and fastest-moving items counted most often (ABC analysis).</li>
<li><strong>Access is controlled</strong> — only stores staff issue stock, and every issue has a recipient and a reason.</li>
<li><strong>Housekeeping</strong> — clean, labelled, safe stores (the "5S" approach: sort, set in order, shine, standardise, sustain).</li>
</ul>
<blockquote><strong>Thekan ERP's golden rule:</strong> if it changes stock quantity, it must create a stock transaction.</blockquote>
HTML,
                ],
                [
                    'title' => 'How stock flows in Thekan ERP',
                    'content' => <<<'HTML'
<p>Think of inventory as a chain:</p>
<ul>
<li><strong>Stock Entries</strong> bring stock <strong>in</strong> — purchases (from goods receipts), customer returns, production output, opening balances.</li>
<li><strong>Stock Issues</strong> take stock <strong>out</strong> — to jobs and projects, to work orders, for sales, scrap or supplier returns.</li>
<li><strong>Stock Transfers</strong> <strong>move</strong> stock between stores — two legs: out of one, into the other.</li>
<li><strong>Stock Adjustments</strong> <strong>correct</strong> differences found in counts, with a reason.</li>
<li><strong>Stock Transactions</strong> record every one of these movements — the <strong>source of truth</strong>.</li>
<li><strong>Stock Levels</strong> show what's available now, calculated from the transactions, per variant and store/shelf.</li>
<li><strong>Low Stock</strong>, <strong>Stock Aging</strong> and the dashboards help you monitor and plan.</li>
</ul>
<h1>Stock is per place</h1>
<p>Stock is tracked per <strong>store</strong> and <strong>shelf</strong>. The workshop store, the main warehouse and a site store each have their own levels. Moving stock between them must be a transfer — otherwise one place shows stock it doesn't have, and another is short.</p>
<blockquote><strong>If stock "disappears", check three things:</strong> (1) the wrong store or shelf, (2) a transfer with an OUT but no IN, or (3) unposted drafts. Start with Stock Transactions.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'What does FIFO mean?', 'options' => [
                    'First in, first out — older stock is used first' => true,
                    'Find it, fix it, order' => false,
                    'Fast in, fast out' => false,
                    'First invoice, first order' => false,
                ]],
                ['q' => 'Which should be counted most often in cycle counting?', 'options' => [
                    'The most valuable and fastest-moving items' => true,
                    'Only items nobody uses' => false,
                    'Nothing — count once a year' => false,
                    'Only tools' => false,
                ]],
                ['q' => 'What is the source of truth for stock in Thekan ERP?', 'options' => [
                    'Stock Transactions' => true,
                    'The storekeeper\'s notebook' => false,
                    'Chat messages' => false,
                    'The purchase requisitions' => false,
                ]],
                ['q' => 'Stock is moved from the warehouse to a site store. How must it be recorded?', 'options' => [
                    'As a stock transfer' => true,
                    'As a new stock entry at the site only' => false,
                    'It needn\'t be recorded' => false,
                    'As a sales invoice' => false,
                ]],
                ['q' => 'Select ALL the first checks when stock seems to have disappeared.', 'options' => [
                    'Was it recorded to the wrong store or shelf?' => true,
                    'Is there a transfer with an OUT but no IN?' => true,
                    'Are there unposted drafts?' => true,
                    'Is it Friday?' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 2: Products, variants, stores and shelves',
            'lessons' => [
                [
                    'title' => 'Products and variants',
                    'content' => <<<'HTML'
<p>Stock is tracked at the level of the <strong>product variant</strong> — the exact item you can pick off a shelf.</p>
<h1>Products</h1>
<p>A <strong>product</strong> is the general item — "MDF board", "Soft-close hinge", "Melamine edging". It has a <strong>name</strong>, a unique <strong>product code</strong>, a <strong>brand</strong>, a <strong>unit</strong>, one or more <strong>categories</strong>, and optionally a description, price, average cost, pack size and images. Manufacturers, brands, units and categories are set up under <strong>Products</strong> in the menu.</p>
<h1>Variants</h1>
<p>A <strong>variant</strong> is a specific version of the product, defined by its <strong>attributes</strong>: "MDF board — 18mm, 2440×1220, moisture-resistant" is a different variant from "MDF board — 12mm, standard". Attribute types (thickness, colour, finish, size) and their values are set up once and reused.</p>
<ul>
<li>Stock, costs, receiving and issuing all happen per variant. That's why goods receipts and stock-fulfilled requisitions insist on a variant.</li>
<li>Each variant can have a <strong>reorder point</strong> — the level at which it shows on Low Stock.</li>
<li>Products and variants can print <strong>QR labels</strong> for shelves and items.</li>
</ul>
<h1>Keeping the item master clean</h1>
<ul>
<li>Search before creating a product or variant — duplicates split stock across two records.</li>
<li>Use attributes consistently: "18mm", not sometimes "18 mm" and sometimes "18".</li>
<li>Use <strong>Stock Lookup</strong> to find any item and where it is.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Stores, shelves and units',
                    'content' => <<<'HTML'
<h1>Stores and shelves</h1>
<p>Under <strong>Storage</strong> in the menu, <strong>Stores</strong> are the places stock is kept (main warehouse, workshop store, a site store), linked to locations, and <strong>Shelves</strong> are the positions inside them.</p>
<ul>
<li>Label every shelf, and put stock where the system says it is.</li>
<li>When you receive, issue or transfer, always confirm the store (and shelf) — the commonest cause of "missing stock" is the right item in the wrong place in the system.</li>
</ul>
<h1>Units of measure</h1>
<p>Every product has a unit — sheet, piece, metre, litre, kilogram, box. Some items are bought in one unit and used in another (a box of 100 screws used by the piece; a roll of edging used by the metre). Unit conversions keep these consistent.</p>
<ul>
<li>Check the unit on every line — "10" means something very different in boxes and in pieces.</li>
<li>Agree units with suppliers on the PO, so receipts match.</li>
</ul>
<h1>Physical stores standards</h1>
<ul>
<li>Store boards flat on level racks, off the floor, away from damp.</li>
<li>Keep finishes, adhesives and solvents in a ventilated, fire-safe area, oldest first.</li>
<li>Restrict entry: only stores staff inside the store.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'At what level is stock tracked in Thekan ERP?', 'options' => [
                    'The product variant, per store and shelf' => true,
                    'The product category only' => false,
                    'The brand' => false,
                    'The supplier' => false,
                ]],
                ['q' => 'What is a variant\'s reorder point for?', 'options' => [
                    'It\'s the level at which the variant appears on Low Stock' => true,
                    'It sets the selling price' => false,
                    'It deletes the variant' => false,
                    'It is the supplier\'s phone number' => false,
                ]],
                ['q' => 'Why must product codes be unique?', 'options' => [
                    'So every item can be identified without doubt and duplicates don\'t split stock' => true,
                    'For decoration' => false,
                    'Because suppliers require it' => false,
                    'They don\'t need to be' => false,
                ]],
                ['q' => 'A box of 100 screws is received but screws are issued by the piece. What keeps this consistent?', 'options' => [
                    'Unit conversions between the units' => true,
                    'Guessing' => false,
                    'Issuing whole boxes only' => false,
                    'A stock adjustment every day' => false,
                ]],
                ['q' => 'Select ALL the good stores practices.', 'options' => [
                    'Label every shelf and keep stock where the system says it is' => true,
                    'Store boards flat, off the floor, away from damp' => true,
                    'Restrict entry to stores staff' => true,
                    'Let anyone take what they need and record it later' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 3: Stock coming in',
            'lessons' => [
                [
                    'title' => 'Stock entries',
                    'content' => <<<'HTML'
<p>A <strong>Stock Entry</strong> brings stock into a store. Most purchased stock arrives through <strong>goods receipts</strong> in Procurement, which create stock entries automatically when posted. You create entries yourself for other receipts — opening balances, production output where not automated, items found during a count (with approval), and similar.</p>
<h1>A stock entry</h1>
<ul>
<li><strong>Store</strong> (required) and shelf, <strong>entry date</strong> and <strong>reference</strong> (PO, GRN or work order number).</li>
<li><strong>Entry type</strong> — <strong>normal</strong>, or <strong>customer return</strong>; with the supplier or customer where relevant.</li>
<li><strong>Lines</strong> — at least one: the variant, a whole-number quantity, and the unit cost.</li>
</ul>
<h1>Statuses</h1>
<ol>
<li><strong>Draft</strong> — being prepared.</li>
<li><strong>Approved</strong> — only drafts can be approved.</li>
<li><strong>Posted</strong> — only approved entries can be posted. Posting creates the stock transactions and increases stock.</li>
</ol>
<p>A posted entry can't be edited. If it's wrong, it must be <strong>unposted</strong> (by someone with permission), corrected and posted again — and the unposting is recorded.</p>
<h1>Control checks</h1>
<ul>
<li>Verify the store and shelf.</li>
<li>Verify quantities and unit cost.</li>
<li>Always attach the reference document.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Customer returns',
                    'content' => <<<'HTML'
<p>When a customer sends goods back — a wrong item, an unwanted extra, a cancelled part of an order — record it under <strong>Returns → Customer Returns</strong>.</p>
<ul>
<li><strong>Store</strong>, <strong>customer</strong> and <strong>entry date</strong> (required), with the reference, remarks and, where it applies, the sales delivery it came from.</li>
<li>The return follows <strong>draft → approved → posted</strong>; posting puts the stock back into the store through a stock entry.</li>
</ul>
<h1>Inspect before you accept</h1>
<ul>
<li>Check the condition. Returned items that are damaged or used shouldn't go back into saleable stock as if new.</li>
<li>Record photos and the reason.</li>
<li>Remember the money side is separate: Sales issues a <strong>credit note</strong> against the invoice. The stores record the goods; finance records the credit.</li>
</ul>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'How does most purchased stock come into the stores in Thekan ERP?', 'options' => [
                    'Through posted goods receipts, which create stock entries automatically' => true,
                    'Through chat' => false,
                    'Through sales invoices' => false,
                    'It doesn\'t need recording' => false,
                ]],
                ['q' => 'Which stock entries can be posted?', 'options' => [
                    'Approved entries' => true,
                    'Draft entries' => false,
                    'Any entry' => false,
                    'Only entries without lines' => false,
                ]],
                ['q' => 'A posted stock entry has the wrong quantity. What is the correct route?', 'options' => [
                    'Unpost it (with permission), correct it and post it again' => true,
                    'Edit the posted entry directly' => false,
                    'Create a duplicate entry' => false,
                    'Leave it' => false,
                ]],
                ['q' => 'A customer returns a scratched cabinet. What should happen?', 'options' => [
                    'Inspect, record the condition and reason, post the return; Sales issues the credit note' => true,
                    'Put it back as new saleable stock without comment' => false,
                    'Throw it away without recording anything' => false,
                    'Refund cash from the stores' => false,
                ]],
                ['q' => 'Select ALL the control checks on a stock entry.', 'options' => [
                    'Verify the store and shelf' => true,
                    'Verify quantities and unit cost' => true,
                    'Attach the reference document' => true,
                    'Skip the reference to save time' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 4: Stock going out and moving',
            'lessons' => [
                [
                    'title' => 'Stock issues',
                    'content' => <<<'HTML'
<p>A <strong>Stock Issue</strong> takes stock out of a store. Every issue says <em>from where</em>, <em>what</em>, <em>how many</em>, <em>for what</em> and <em>to whom</em>.</p>
<h1>An issue</h1>
<ul>
<li><strong>Issue type</strong> — <strong>normal</strong>, <strong>supplier return</strong>, <strong>BOM</strong> (to production), <strong>sales</strong>, <strong>scrap</strong>, <strong>transfer</strong> or <strong>requisition</strong>.</li>
<li><strong>From store</strong> (required), issue date, reference and reason.</li>
<li>The <strong>project</strong> and <strong>project component</strong> it's for — so the material cost lands on the right job.</li>
<li>Lines: the variant, quantity and unit cost.</li>
</ul>
<h1>Statuses</h1>
<p><strong>Draft</strong> → <strong>approved</strong> → <strong>posted</strong>. Posting reduces stock. Only drafts can be deleted; a posted issue must be unposted to change it.</p>
<h1>Acknowledging receipt</h1>
<p>Once an issue is <strong>approved</strong>, the person receiving the goods acknowledges them — by <strong>signature</strong>, by entering their <strong>password</strong>, or by <strong>proxy</strong> (someone receiving on their behalf). The recipient employee is recorded. This ends "I never got it" disputes: it's clear who took what, and when.</p>
<h1>Scrap</h1>
<p>Damaged or unusable stock is issued with type <strong>scrap</strong> and a clear reason — never just left to "disappear".</p>
HTML,
                ],
                [
                    'title' => 'Tool loans',
                    'content' => <<<'HTML'
<p>Power tools, ladders, levels and specialist equipment go out to sites and come back. Recording them as <strong>loans</strong> means we always know who has what.</p>
<ul>
<li>Issue the tool with an <strong>expected return date</strong> on the line. That marks the line as a loan.</li>
<li>Loans appear under <strong>Borrowed Stock / Tool Loans</strong>, showing what's out, with whom, and what's overdue.</li>
<li>When the tool comes back, check its condition and <strong>mark the line returned</strong>. A line can be marked returned only once, and only if it was issued as a loan.</li>
</ul>
<h1>Good practice</h1>
<ul>
<li>Every loan has a named recipient and an acknowledgement.</li>
<li>Chase overdue loans weekly. Tools that wander between sites are tools that get lost.</li>
<li>Inspect tools on return; report damage, and take unsafe tools out of service.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Transfers and supplier returns',
                    'content' => <<<'HTML'
<h1>Stock transfers</h1>
<p>A <strong>Stock Transfer</strong> moves stock between stores — warehouse to workshop, warehouse to a site store. It creates <strong>two</strong> transactions: <strong>TRANSFER_OUT</strong> from the source and <strong>TRANSFER_IN</strong> to the destination.</p>
<ul>
<li>Lines: the variant and quantity, with a reason.</li>
<li><strong>Draft</strong> transfers can be edited. <strong>Posting</strong> writes both legs and <strong>locks</strong> the transfer. Posted transfers can't be deleted.</li>
<li>Post the transfer when the stock actually moves, not days later.</li>
</ul>
<h1>Supplier returns</h1>
<p>Goods going back to a supplier — rejected, damaged or wrong — are recorded under <strong>Returns → Supplier Returns</strong>: store, supplier, reason, reference and date, then <strong>draft → approved → posted</strong>, which takes the stock out. Finance records the supplier's credit.</p>
<p>The <strong>Supp-Ret. Analytics</strong> view shows returns by supplier — useful evidence when reviewing supplier quality.</p>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Why link a stock issue to a project and component?', 'options' => [
                    'So the material cost lands on the right job' => true,
                    'To print it in colour' => false,
                    'It changes the store' => false,
                    'It isn\'t possible' => false,
                ]],
                ['q' => 'What must happen before receipt of an issue can be acknowledged?', 'options' => [
                    'The issue must be approved' => true,
                    'It must be deleted' => false,
                    'The supplier must be paid' => false,
                    'Nothing' => false,
                ]],
                ['q' => 'How is a line marked as a tool loan?', 'options' => [
                    'It is issued with an expected return date' => true,
                    'By writing "loan" in the description' => false,
                    'By a chat message' => false,
                    'All issues are loans' => false,
                ]],
                ['q' => 'How many stock transactions does a posted transfer create?', 'options' => [
                    'Two — an OUT from the source and an IN to the destination' => true,
                    'One' => false,
                    'None' => false,
                    'Three' => false,
                ]],
                ['q' => 'Select ALL the stock issue types.', 'options' => [
                    'BOM (to production)' => true,
                    'Scrap' => true,
                    'Supplier return and sales' => true,
                    'Payroll' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 5: Accuracy, adjustments and counts',
            'lessons' => [
                [
                    'title' => 'Transactions, levels and counts',
                    'content' => <<<'HTML'
<h1>Stock Transactions — the ledger</h1>
<p><strong>Stock Transactions</strong> lists every movement: in, out, transfer, adjustment. Filter by variant, date range, store, type or reference to trace exactly what happened. When anything looks wrong, start here.</p>
<h1>Stock Levels — what's there now</h1>
<p><strong>Stock Levels</strong> shows what's available now, by variant and store/shelf, calculated from the transactions. Check it before issuing, transferring or promising stock to a job.</p>
<h1>Counting</h1>
<ul>
<li><strong>Cycle counts:</strong> count a few items every week — high-value and fast-moving items most often.</li>
<li><strong>Full counts:</strong> at least once a year, and at year-end for the accounts.</li>
<li><strong>Count blind:</strong> counters record what they find, without looking at the system figure first.</li>
<li>Post all pending drafts before you count, so the system figure is complete.</li>
<li>Recount any significant difference before adjusting, and investigate the cause.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Stock adjustments',
                    'content' => <<<'HTML'
<p>A <strong>Stock Adjustment</strong> corrects the system to match reality after a count or investigation.</p>
<ul>
<li>Choose the <strong>variant</strong> and <strong>store</strong>, and enter the <strong>quantity change</strong> — positive to add, negative to remove. Zero isn't allowed.</li>
<li>A <strong>reason</strong> is required, with optional notes: "Cycle count 14 Nov — 3 sheets water-damaged, disposed", "Found on shelf B4 during count".</li>
<li>The ERP won't let an adjustment take stock below zero.</li>
<li>The adjustment is recorded as a stock transaction, valued at the current average cost.</li>
</ul>
<h1>Adjust only after you understand</h1>
<p>An adjustment is the last step, not the first. Before adjusting, check for the usual causes — wrong store, missing transfer leg, unposted drafts, issues not recorded. Correcting the real cause fixes the job costs too; an adjustment alone just hides the problem.</p>
<blockquote><strong>Large or repeated adjustments are a warning sign.</strong> Report them to your manager; they may point to theft, waste or a process that isn't being followed.</blockquote>
HTML,
                ],
            ],
            'test' => [
                ['q' => 'Where do you start investigating a stock difference?', 'options' => [
                    'Stock Transactions, filtered by variant, store and date' => true,
                    'The sales analytics' => false,
                    'Payroll' => false,
                    'A stock adjustment' => false,
                ]],
                ['q' => 'What is a "blind" count?', 'options' => [
                    'Counters record what they find without seeing the system figure first' => true,
                    'Counting in the dark' => false,
                    'Not counting at all' => false,
                    'Copying the system figure' => false,
                ]],
                ['q' => 'What is required on every stock adjustment?', 'options' => [
                    'A non-zero quantity change and a reason' => true,
                    'A sales invoice' => false,
                    'A supplier quotation' => false,
                    'Nothing' => false,
                ]],
                ['q' => 'An adjustment of −10 is entered where only 6 are on hand. What happens?', 'options' => [
                    'It is refused — stock can\'t go negative' => true,
                    'Stock goes to −4' => false,
                    'It is posted and ignored' => false,
                    'The variant is deleted' => false,
                ]],
                ['q' => 'Select ALL the checks before making an adjustment.', 'options' => [
                    'Wrong store or shelf' => true,
                    'Missing transfer leg' => true,
                    'Unposted drafts or unrecorded issues' => true,
                    'The weather forecast' => false,
                ]],
            ],
        ],

        // ------------------------------------------------------------------
        [
            'title' => 'Module 6: Planning, ageing and dashboards',
            'lessons' => [
                [
                    'title' => 'Low stock and replenishment',
                    'content' => <<<'HTML'
<p><strong>Low Stock Levels</strong> lists variants at or below their <strong>reorder point</strong>. Low stock also appears on the dashboard of people who look after inventory.</p>
<h1>Setting reorder points</h1>
<p>A simple, widely used rule: <strong>reorder point = average daily usage × supplier lead time in days + safety stock</strong>.</p>
<ul>
<li>Example: the workshop uses 20 hinges a day; the supplier takes 10 days; safety stock 100. Reorder point = 20 × 10 + 100 = <strong>300</strong>.</li>
<li>Critical, fast-moving items need reorder points; slow, cheap items may not.</li>
<li>Review reorder points when usage or lead times change.</li>
</ul>
<h1>Acting on low stock</h1>
<ul>
<li>Raise a purchase requisition (fulfilment: from supplier), or transfer from another store that has surplus.</li>
<li>Check upcoming jobs and work orders — the project programme may need more than normal usage suggests.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Stock ageing and the dashboards',
                    'content' => <<<'HTML'
<h1>Stock Aging</h1>
<p><strong>Stock Aging</strong> shows how long stock has sat without moving, in age buckets (0–30, 31–60 and 61–90+ days). Old stock means cash tied up, space wasted, and risk of damage or expiry.</p>
<ul>
<li>Use ageing stock on suitable jobs first.</li>
<li>Check dated items (adhesives, lacquers) for expiry.</li>
<li>Feed slow movers back to procurement — stop reordering them.</li>
<li>Agree disposal of dead stock with management, recorded properly as scrap.</li>
</ul>
<h1>Dashboards and analytics</h1>
<ul>
<li><strong>Stock Inventory</strong> and <strong>Stock Levels</strong> dashboards — value and quantity by store.</li>
<li><strong>Stock Entry</strong> analytics — what's coming in.</li>
<li><strong>Stock Transfers</strong> dashboard — movement between stores.</li>
</ul>
<h1>The SOP</h1>
<p><strong>Work Flow Help</strong> in the Stock Management menu explains the whole workflow, and <strong>Print / Export SOP</strong> produces it as a document for onboarding new stores staff.</p>
<p>Mark this lesson complete, then take the final assessment: 20 questions in 30 minutes. You need 80% to pass, with up to 3 attempts.</p>
HTML,
                ],
            ],
            'final' => [
                'title' => 'Inventory & Stores — Final Assessment',
                'slug' => 'erp-inventory-assessment',
                'description' => '<p>The final assessment for Inventory &amp; Stores in Thekan ERP, covering stock-control practice and every inventory movement in the ERP.</p>',
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
                    ['q' => 'What is Thekan ERP\'s golden rule for stock?', 'options' => [
                        'If it changes stock quantity, it must create a stock transaction' => true,
                        'Count once a year only' => false,
                        'Adjust whenever numbers look odd' => false,
                        'Only managers record stock' => false,
                    ]],
                    ['q' => 'Which method suits adhesives and lacquers?', 'options' => [
                        'First expired, first out (FEFO)' => true,
                        'Last in, first out' => false,
                        'Random' => false,
                        'Biggest tin first' => false,
                    ]],
                    ['q' => 'Why must goods receipts specify a variant?', 'options' => [
                        'Stock is tracked per variant, so the exact item must be known' => true,
                        'For the PDF' => false,
                        'Suppliers require it' => false,
                        'It isn\'t required' => false,
                    ]],
                    ['q' => 'You find "MDF 18mm" set up twice as two products. What is the risk?', 'options' => [
                        'Stock and history are split across two records' => true,
                        'None' => false,
                        'The price doubles' => false,
                        'The supplier is blocked' => false,
                    ]],
                    ['q' => 'What is the stock entry status flow?', 'options' => [
                        'Draft → Approved → Posted' => true,
                        'Open → Resolved → Closed' => false,
                        'New → Won → Lost' => false,
                        'Pending → Completed → Overdue' => false,
                    ]],
                    ['q' => 'What does posting a stock issue do?', 'options' => [
                        'Reduces stock in the from-store' => true,
                        'Increases stock' => false,
                        'Pays a supplier' => false,
                        'Nothing until month-end' => false,
                    ]],
                    ['q' => 'Select ALL the ways receipt of an issue can be acknowledged.', 'options' => [
                        'Signature' => true,
                        'Password' => true,
                        'Proxy' => true,
                        'Thumbs-up emoji' => false,
                    ]],
                    ['q' => 'A drill was lent to a site and has come back. What do you do in the ERP?', 'options' => [
                        'Inspect it and mark the loan line returned' => true,
                        'Create a new stock entry' => false,
                        'Do nothing' => false,
                        'Delete the issue' => false,
                    ]],
                    ['q' => 'Where can you see every tool currently on loan and what is overdue?', 'options' => [
                        'Borrowed Stock / Tool Loans' => true,
                        'Sales Analytics' => false,
                        'Payroll Runs' => false,
                        'Chat' => false,
                    ]],
                    ['q' => 'Can a posted transfer be edited or deleted?', 'options' => [
                        'No — posting locks it' => true,
                        'Yes, any time' => false,
                        'Only on weekends' => false,
                        'Only by the receiving store' => false,
                    ]],
                    ['q' => 'Damaged boards are rejected and sent back to the supplier. How are they recorded?', 'options' => [
                        'As a supplier return (draft → approved → posted); finance records the credit' => true,
                        'As a customer return' => false,
                        'They aren\'t recorded' => false,
                        'As a stock entry' => false,
                    ]],
                    ['q' => 'Stock Levels show 50 sheets but the shelf has 30. What is the right first step?', 'options' => [
                        'Recount, then investigate Stock Transactions before any adjustment' => true,
                        'Adjust −20 immediately' => false,
                        'Ignore it' => false,
                        'Order 20 more' => false,
                    ]],
                    ['q' => 'What should you do before a count?', 'options' => [
                        'Post all pending drafts so the system figure is complete' => true,
                        'Delete all drafts' => false,
                        'Adjust everything to zero' => false,
                        'Stop recording issues for a month' => false,
                    ]],
                    ['q' => 'Select ALL the true statements about stock adjustments.', 'options' => [
                        'A reason is required' => true,
                        'The quantity change can\'t be zero' => true,
                        'It can\'t take stock below zero' => true,
                        'It is the best first response to any difference' => false,
                    ]],
                    ['q' => 'Usage 15 a day, lead time 8 days, safety stock 60. What is the reorder point?', 'options' => [
                        '180' => true,
                        '120' => false,
                        '83' => false,
                        '23' => false,
                    ]],
                    ['q' => 'An item appears on Low Stock. Select ALL sensible actions.', 'options' => [
                        'Raise a purchase requisition from a supplier' => true,
                        'Transfer surplus from another store' => true,
                        'Check upcoming jobs and work orders for extra demand' => true,
                        'Raise the reorder point to hide it' => false,
                    ]],
                    ['q' => 'What does Stock Aging show?', 'options' => [
                        'How long stock has sat without moving, in age buckets' => true,
                        'The age of the storekeeper' => false,
                        'Supplier payment terms' => false,
                        'Sales trends' => false,
                    ]],
                    ['q' => 'Dead stock has been agreed for disposal. How should it leave the system?', 'options' => [
                        'As a stock issue of type scrap, with a reason' => true,
                        'Just throw it away' => false,
                        'As a sales invoice to nobody' => false,
                        'Leave it on the system' => false,
                    ]],
                    ['q' => 'Large, repeated adjustments on the same items suggest what?', 'options' => [
                        'Possible theft, waste or a process not being followed — report it' => true,
                        'Nothing — they\'re normal' => false,
                        'The ERP is broken' => false,
                        'Good stock control' => false,
                    ]],
                    ['q' => 'Where can a new storekeeper find the stock workflow written as an SOP?', 'options' => [
                        'Work Flow Help and Print / Export SOP in the Stock Management menu' => true,
                        'In the CRM' => false,
                        'In the payroll run' => false,
                        'Nowhere' => false,
                    ]],
                ],
            ],
        ],
    ],

    'recommended_for' => ['Storekeeper', 'Procurement Officer', 'Production Supervisor'],
];
