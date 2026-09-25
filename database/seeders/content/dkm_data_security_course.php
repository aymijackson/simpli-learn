<?php

/*
|--------------------------------------------------------------------------
| Data Security & Confidentiality Essentials — D'Kings Men Media
|--------------------------------------------------------------------------
|
| Course, module knowledge checks and final assessment for the team that
| runs the CREAM platform (web, app and USSD *463#): fan accounts, music
| and video sales, CREAM Fund instalments, celebrity auctions, games and
| talent discovery. Used by DKingsMenMediaSeeder.
|
| Lesson HTML sticks to what the Trix editor supports (h1, p, ul, ol,
| blockquote, strong, em) so owners can edit it later in the admin.
|
| Lesson 'image' entries point at files in public/images/courses/dkm-data-security
| (exported from the quick-training slide deck); the seeder places each one
| after the lesson's opening paragraph.
|
| Question format: 'q' => text, 'options' => [text => is_correct].
| More than one correct option makes it a "select all that apply" question.
|
*/

return [
    'course' => [
        'title' => 'Data Security & Confidentiality Essentials',
        'slug' => 'data-security-confidentiality-essentials',
        'description' => <<<'HTML'
<p>Every day, the D'Kings Men Media team looks after millions of fans' personal details, their payments and CREAM Fund plans, auction winners' delivery addresses, talent-show entries, and unreleased music from our artists. This course shows you how to protect all of it.</p>
<p>You'll learn what Nigeria's Data Protection Act 2023 expects of us, how to keep unreleased content and business information confidential, the everyday habits that stop most attacks, how to handle fan and payment data safely, and exactly what to do when something goes wrong.</p>
<ul>
<li><strong>Who it's for:</strong> everyone at D'Kings Men Media — staff, contractors and interns — whatever your role.</li>
<li><strong>How it works:</strong> 6 short modules, each with a 5-question knowledge check, then a final assessment.</li>
<li><strong>To complete it:</strong> finish every lesson and score at least 80% on the final assessment. You'll then receive a certificate of completion.</li>
</ul>
HTML,
    ],

    'modules' => [
        [
            'title' => 'Module 1: Why data security matters at D\'Kings Men Media',
            'lessons' => [
                [
                    'title' => 'Welcome and how this course works',
                    'image' => ['file' => 'course-overview.png', 'alt' => 'Course overview: the six modules — why data security matters, the law, confidentiality, everyday habits, fan and payment data, and incidents.'],
                    'preview' => true,
                    'content' => <<<'HTML'
<p>Welcome! CREAM exists because fans trust us to connect them directly with the artists they love. That trust depends on something fans rarely see: how carefully we look after their information and our artists' work.</p>
<p>This course takes about 60–90 minutes in total. You can stop at any time and pick up where you left off.</p>
<h1>What you'll be able to do by the end</h1>
<ul>
<li>Recognise the personal and confidential information you handle in your role.</li>
<li>Explain our main obligations under the Nigeria Data Protection Act 2023 (NDPA).</li>
<li>Classify information and handle it according to its sensitivity.</li>
<li>Spot phishing and social-engineering attempts, including ones aimed at fan accounts.</li>
<li>Handle fan, payment and partner data safely day to day.</li>
<li>Report a security incident quickly and correctly.</li>
</ul>
<h1>How to complete the course</h1>
<ol>
<li>Work through each lesson and select <strong>Mark as complete</strong> at the end.</li>
<li>Take the short knowledge check at the end of each module. They're practice — retake them as often as you like.</li>
<li>When every lesson is complete, take the <strong>Final Assessment</strong>. You need 80% to pass and you have up to 3 attempts.</li>
</ol>
<blockquote>Security isn't just the IT team's job. Most data breaches start with an everyday action by an ordinary employee — a clicked link, a shared password, a file sent to the wrong person. You are our strongest defence.</blockquote>
HTML,
                ],
                [
                    'title' => 'What we protect: the CREAM data map',
                    'image' => ['file' => 'cream-data-map.png', 'alt' => 'The CREAM data map: fans and subscribers, money, talent and artists, and our business.'],
                    'content' => <<<'HTML'
<p>You can't protect information you don't know you have. Here is the information D'Kings Men Media handles across the CREAM platform.</p>
<h1>Fans and subscribers</h1>
<ul>
<li>Names, phone numbers, email addresses and passwords for web, app and USSD (*463#) accounts.</li>
<li>Subscription status, purchases of songs and videos, streams, votes and game activity (C.R.E.A.M Bid, B.O.W and others).</li>
<li>Device details and location data collected by the app.</li>
</ul>
<h1>Money</h1>
<ul>
<li>Transaction records, payment references and refunds.</li>
<li><strong>CREAM Fund</strong> ("pay small small") instalment plans — these reveal a person's financial situation and deserve extra care.</li>
<li>Auction bids, winners' identities and their <strong>delivery addresses</strong>.</li>
<li>Artist royalties, payouts and bank details.</li>
</ul>
<h1>Talent and artists</h1>
<ul>
<li>Talent-discovery entries (for example, the MTN BOTS programme): contestants' personal details, photos, videos and sometimes the details of <strong>minors</strong> and their parents.</li>
<li><strong>Unreleased music, videos and artwork</strong>, stems and masters, release dates and campaign plans.</li>
<li>Artists' and celebrities' private contact details, addresses, schedules and contracts.</li>
</ul>
<h1>Our business</h1>
<ul>
<li>Partner and telco agreements, commercial terms, revenue figures and strategy.</li>
<li>Staff records, system passwords, admin dashboards and API keys.</li>
</ul>
<blockquote><strong>Think about it:</strong> which of these do you touch in a normal week? Keep that list in mind as you go through the course.</blockquote>
HTML,
                ],
                [
                    'title' => 'What\'s at stake when data leaks',
                    'image' => ['file' => 'whats-at-stake.png', 'alt' => 'What is at stake when data leaks, for fans, artists and the company, including NDPA fines of up to ₦10 million or 2% of annual gross revenue.'],
                    'content' => <<<'HTML'
<p>A single careless moment can cause harm that is hard or impossible to undo.</p>
<h1>For fans</h1>
<ul>
<li><strong>Fraud and SIM-swap attacks:</strong> leaked phone numbers plus personal details let criminals take over mobile money and bank accounts.</li>
<li><strong>Scams:</strong> fraudsters pretending to be CREAM or an artist use leaked details to make fake "prize" and "auction" messages look convincing.</li>
<li><strong>Safety:</strong> an auction winner's home address or a young contestant's details in the wrong hands can put people at physical risk.</li>
</ul>
<h1>For our artists</h1>
<ul>
<li>A leaked unreleased track can wreck a release campaign and cost the artist and the company real money.</li>
<li>Leaked private details can lead to harassment or security threats.</li>
</ul>
<h1>For D'Kings Men Media</h1>
<ul>
<li><strong>Regulatory penalties:</strong> under the NDPA, fines can reach the higher of ₦10 million or 2% of annual gross revenue for organisations of major importance.</li>
<li><strong>Lost partnerships:</strong> telcos, payment providers and brands only work with partners they trust.</li>
<li><strong>Lost fans:</strong> trust is the product. Once it's gone, fans don't come back.</li>
</ul>
<blockquote><strong>Scenario:</strong> A staff member exports a list of 50,000 subscriber phone numbers to share with a promoter "just for one campaign". The file is forwarded several times and ends up on a spam forum. Within weeks, fans receive fake messages offering "CREAM prizes" in exchange for their OTP codes. Every step of that chain could have been stopped by the practices in this course.</blockquote>
HTML,
                ],
            ],
            'quiz' => [
                ['q' => 'Which of these is personal data that D\'Kings Men Media handles?', 'options' => [
                    'A fan\'s phone number used to subscribe via USSD *463#' => true,
                    'The published lyrics of a released song' => false,
                    'The public address of a concert venue' => false,
                    'An artist\'s publicly released album cover' => false,
                ]],
                ['q' => 'Why do CREAM Fund instalment records need extra care?', 'options' => [
                    'They reveal information about a person\'s financial situation' => true,
                    'They are publicly available anyway' => false,
                    'They only contain song titles' => false,
                    'They are never linked to a person' => false,
                ]],
                ['q' => 'Select ALL the items that are confidential to D\'Kings Men Media or its artists.', 'options' => [
                    'An unreleased single and its release date' => true,
                    'An artist\'s private phone number' => true,
                    'Commercial terms of a telco partnership' => true,
                    'A song that is already live on the platform' => false,
                ]],
                ['q' => 'How can leaked fan phone numbers lead to financial harm?', 'options' => [
                    'Criminals combine them with other details for SIM-swap fraud and convincing scams' => true,
                    'They can\'t — phone numbers are harmless' => false,
                    'They only cause more marketing messages' => false,
                    'Phone numbers can be used to download songs for free' => false,
                ]],
                ['q' => 'Who is responsible for protecting the data we handle?', 'options' => [
                    'Everyone at D\'Kings Men Media, whatever their role' => true,
                    'Only the IT team' => false,
                    'Only senior management' => false,
                    'Only the payment provider' => false,
                ]],
            ],
        ],

        [
            'title' => 'Module 2: The law — Nigeria Data Protection Act 2023',
            'lessons' => [
                [
                    'title' => 'NDPA essentials: the seven principles',
                    'image' => ['file' => 'ndpa-seven-principles.png', 'alt' => 'The seven principles of the Nigeria Data Protection Act 2023, and the lawful bases for using personal data.'],
                    'content' => <<<'HTML'
<p>The <strong>Nigeria Data Protection Act 2023 (NDPA)</strong> is the main law on personal data in Nigeria. It is enforced by the <strong>Nigeria Data Protection Commission (NDPC)</strong>, which can investigate complaints, audit organisations and impose penalties.</p>
<p>As a company that decides why and how fan data is used, D'Kings Men Media is a <strong>data controller</strong>. Companies that process data on our behalf — hosting providers, SMS aggregators, agencies — are <strong>data processors</strong>, and we remain responsible for choosing and supervising them.</p>
<h1>The principles you must follow</h1>
<ol>
<li><strong>Lawful, fair and transparent:</strong> only use data in ways people would reasonably expect and that we have told them about.</li>
<li><strong>Purpose limitation:</strong> collect data for a specific purpose and don't reuse it for something unrelated.</li>
<li><strong>Data minimisation:</strong> collect only what we actually need.</li>
<li><strong>Accuracy:</strong> keep data correct and up to date.</li>
<li><strong>Storage limitation:</strong> don't keep data longer than necessary.</li>
<li><strong>Integrity and confidentiality:</strong> keep data secure against loss, leaks and unauthorised access.</li>
<li><strong>Accountability:</strong> be able to show that we comply.</li>
</ol>
<h1>We need a lawful basis</h1>
<p>Every use of personal data needs one of these legal grounds: <strong>consent</strong>, performance of a <strong>contract</strong> (for example, delivering a song a fan paid for), a <strong>legal obligation</strong>, protecting someone's <strong>vital interests</strong>, a task in the <strong>public interest</strong>, or our <strong>legitimate interests</strong> where they don't override the person's rights.</p>
<blockquote><strong>Example:</strong> a fan's phone number collected to deliver a purchased song (contract) can't simply be passed to a brand partner for their own advertising. That's a new purpose and it needs its own lawful basis — usually the fan's consent.</blockquote>
HTML,
                ],
                [
                    'title' => 'Fans\' rights and how we respond',
                    'image' => ['file' => 'fans-rights.png', 'alt' => 'Data subject rights, and the four steps to follow when a fan makes a request about their data.'],
                    'content' => <<<'HTML'
<p>The NDPA gives every fan — and every artist and staff member — rights over their personal data.</p>
<ul>
<li><strong>Be informed</strong> about how their data is used.</li>
<li><strong>Access</strong> a copy of their data.</li>
<li><strong>Correct</strong> inaccurate data.</li>
<li><strong>Erasure</strong> — have their data deleted in many situations.</li>
<li><strong>Restrict</strong> or <strong>object to</strong> processing, including direct marketing.</li>
<li><strong>Data portability</strong> — receive their data in a usable format.</li>
<li><strong>Withdraw consent</strong> at any time, as easily as they gave it.</li>
<li>Not be subject to decisions based solely on <strong>automated processing</strong> that significantly affect them, with some exceptions.</li>
</ul>
<h1>What to do when a request comes in</h1>
<ol>
<li>A request can arrive anywhere — a support ticket, a social media DM, an email, even a comment. It doesn't need to use the words "data protection".</li>
<li><strong>Forward it immediately</strong> to the Data Protection Officer (DPO). The law sets deadlines for responding, so don't sit on it.</li>
<li><strong>Verify identity</strong> before releasing or changing anything. A request that gives someone else's data to an impostor is itself a breach.</li>
<li>Never argue with, ignore or discourage a request.</li>
</ol>
<blockquote><strong>Scenario:</strong> A fan tweets: "Stop sending me SMS promos and delete my CREAM account!" This is both an objection to marketing and an erasure request. Log it and pass it to the DPO straight away.</blockquote>
HTML,
                ],
                [
                    'title' => 'Consent, marketing and children',
                    'image' => ['file' => 'consent-and-children.png', 'alt' => 'Under 18 counts as a child under the NDPA; what valid consent looks like; rules for marketing messages.'],
                    'content' => <<<'HTML'
<h1>Valid consent</h1>
<p>When we rely on consent — for example, for promotional SMS or sharing data with a sponsor — it must be <strong>freely given, specific, informed and unambiguous</strong>. Pre-ticked boxes and silence don't count. We must be able to prove consent was given, and people must be able to withdraw it easily (for example, by replying STOP or through a USSD menu option).</p>
<h1>Marketing messages</h1>
<ul>
<li>Only send promotional SMS, push notifications or emails to people who have agreed to receive them.</li>
<li>Honour opt-outs immediately, across every channel.</li>
<li>Telecom rules on unsolicited messages (such as Do-Not-Disturb preferences) apply on top of the NDPA — check with the partnerships team before any bulk campaign.</li>
</ul>
<h1>Children and young people</h1>
<p>Under the NDPA a <strong>child is anyone under 18</strong>. Processing a child's personal data requires the consent of a <strong>parent or legal guardian</strong>, and we must take reasonable steps to verify age and consent.</p>
<ul>
<li>Talent-discovery entries from under-18s need recorded parental or guardian consent before we use their details, photos or videos.</li>
<li>Never publish a minor's contact details, school or location.</li>
<li>Collect the minimum and delete entries once they're no longer needed.</li>
</ul>
<blockquote>If you're unsure whether we have the right consent for something, <strong>stop and ask the DPO first</strong>. It's far easier to check beforehand than to fix it afterwards.</blockquote>
HTML,
                ],
            ],
            'quiz' => [
                ['q' => 'Which body enforces the Nigeria Data Protection Act 2023?', 'options' => [
                    'The Nigeria Data Protection Commission (NDPC)' => true,
                    'The Nigerian Copyright Commission' => false,
                    'The Central Bank of Nigeria' => false,
                    'The Corporate Affairs Commission' => false,
                ]],
                ['q' => 'Collecting only the data we actually need is called:', 'options' => [
                    'Data minimisation' => true,
                    'Purpose limitation' => false,
                    'Data portability' => false,
                    'Storage limitation' => false,
                ]],
                ['q' => 'Under the NDPA, who is a "child"?', 'options' => [
                    'Anyone under 18' => true,
                    'Anyone under 13' => false,
                    'Anyone under 16' => false,
                    'Anyone still in secondary school, regardless of age' => false,
                ]],
                ['q' => 'A fan DMs the CREAM page asking what data we hold about them. What should you do?', 'options' => [
                    'Forward it to the DPO immediately and help verify the person\'s identity' => true,
                    'Ignore it — requests must come by formal letter' => false,
                    'Screenshot their account data and send it straight back in the DM' => false,
                    'Tell them this information is confidential' => false,
                ]],
                ['q' => 'Select ALL the features of valid consent under the NDPA.', 'options' => [
                    'Freely given' => true,
                    'Specific and informed' => true,
                    'Can be withdrawn easily' => true,
                    'Assumed if the person doesn\'t reply' => false,
                ]],
            ],
        ],

        [
            'title' => 'Module 3: Confidentiality in the entertainment business',
            'lessons' => [
                [
                    'title' => 'Classifying information: Public to Restricted',
                    'image' => ['file' => 'information-classification.png', 'alt' => 'The four information levels — Public, Internal, Confidential and Restricted — with examples and handling rules.'],
                    'content' => <<<'HTML'
<p>Not all information needs the same protection. We use four levels. When in doubt, choose the higher level.</p>
<h1>Public</h1>
<p>Approved for anyone to see: released songs and videos, published press releases, public campaign posts. <em>Handling:</em> no restrictions, but only share the approved final version.</p>
<h1>Internal</h1>
<p>For D'Kings Men Media staff only: internal announcements, general procedures, team schedules. <em>Handling:</em> keep within the company; don't post it publicly.</p>
<h1>Confidential</h1>
<p>Limited to people who need it for their job: fan and subscriber data, transaction records, partner agreements, financial reports, staff records. <em>Handling:</em> company systems only, access on a need-to-know basis, never on personal accounts or devices.</p>
<h1>Restricted</h1>
<p>Our most sensitive information, where a leak causes serious harm: <strong>unreleased music and masters</strong>, release dates before announcement, artist contracts and royalties, celebrity private details, auction consignors and winners, CREAM Fund financial details, passwords and API keys, bulk data exports. <em>Handling:</em> named individuals only, encrypted storage and transfer, no printing without approval, and every share is logged.</p>
<blockquote><strong>Quick test:</strong> "If this appeared on a blog tomorrow, how bad would it be?" Embarrassing → Internal. Harmful to fans, artists or the business → Confidential. Seriously damaging or irreversible → Restricted.</blockquote>
HTML,
                ],
                [
                    'title' => 'Protecting unreleased content and embargoes',
                    'image' => ['file' => 'unreleased-content.png', 'alt' => 'What to do and what to avoid when protecting unreleased music, videos and artwork.'],
                    'content' => <<<'HTML'
<p>Leaks of unreleased music are one of the biggest confidentiality risks in our industry, and they almost always come from inside the circle of people with access.</p>
<h1>Golden rules for unreleased content</h1>
<ul>
<li><strong>Need-to-know only:</strong> share with the fewest people possible, and only the version they need (a snippet or low-quality preview rather than the master).</li>
<li><strong>Approved channels only:</strong> use the company's secure file-sharing with named recipients, expiring links and download limits. <strong>Never</strong> send unreleased tracks over WhatsApp, Telegram, personal email or personal cloud storage.</li>
<li><strong>Watermark</strong> previews sent outside the company so a leak can be traced.</li>
<li><strong>No recording in sessions:</strong> don't film or record studio sessions, listening sessions or rehearsals on personal phones unless the artist and management have approved it.</li>
<li><strong>Embargoes are absolute:</strong> release dates, features and artwork stay secret until the official announcement — including "teasers" to friends or on your personal social media.</li>
<li><strong>Clean up:</strong> delete local copies once your work is done, and revoke shared links after the release.</li>
</ul>
<blockquote><strong>Scenario:</strong> A friend at a radio station asks you to "just send the new single early so we can prepare". Even if they're trustworthy, the answer is no. Refer them to the promotions team, who can send an approved, watermarked copy under an embargo agreement.</blockquote>
HTML,
                ],
                [
                    'title' => 'Artists, partners and talking about work',
                    'content' => <<<'HTML'
<h1>Artists and celebrities</h1>
<p>Working close to well-known artists means handling details that fans, the press and criminals would love to have: home addresses, private phone numbers, travel plans, health information and personal relationships. Treat all of it as <strong>Restricted</strong>. Never confirm an artist's whereabouts or plans to anyone outside the need-to-know group.</p>
<h1>Contracts and commercial information</h1>
<p>Deal terms, royalties, telco and brand partnership agreements, auction reserve prices and revenue figures are confidential. Many are also covered by non-disclosure agreements (NDAs) that D'Kings Men Media has signed — breaking them can lead to legal action against the company and against individuals.</p>
<h1>Talking about work</h1>
<ul>
<li>Don't discuss confidential matters in public places — clubs, events, taxis, backstage or on calls in open areas.</li>
<li>Don't post photos from offices, studios or screens where documents, dashboards or whiteboards are visible.</li>
<li>Don't comment on rumours or confirm anything about artists or deals on social media. Refer press and bloggers to the communications team.</li>
<li>Your confidentiality duty continues after you leave D'Kings Men Media.</li>
</ul>
<blockquote><strong>Remember:</strong> "Everyone already knows" isn't a reason to confirm something. Until it's officially announced, it's confidential.</blockquote>
HTML,
                ],
            ],
            'quiz' => [
                ['q' => 'What classification should an unreleased master recording have?', 'options' => [
                    'Restricted' => true,
                    'Internal' => false,
                    'Public' => false,
                    'Confidential only after release' => false,
                ]],
                ['q' => 'What is the approved way to send an unreleased track to a remix producer?', 'options' => [
                    'Company secure file-sharing with a named recipient, an expiring link and a watermark' => true,
                    'A WhatsApp voice note, because it\'s quick' => false,
                    'Your personal Google Drive with a public link' => false,
                    'A USB stick left at reception' => false,
                ]],
                ['q' => 'A blogger DMs you asking if a rumoured collaboration is real. It is, but hasn\'t been announced. What do you do?', 'options' => [
                    'Don\'t confirm or deny; refer them to the communications team' => true,
                    'Confirm it, since the rumour is already out' => false,
                    'Hint at it with an emoji' => false,
                    'Send them the artwork so they get the facts right' => false,
                ]],
                ['q' => 'Select ALL the actions that break our rules on unreleased content.', 'options' => [
                    'Recording a studio session on your personal phone without approval' => true,
                    'Posting a "guess what\'s coming" teaser on your personal Instagram' => true,
                    'Keeping a local copy of the master after your work is finished' => true,
                    'Sharing a watermarked preview through the approved system with a named recipient' => false,
                ]],
                ['q' => 'When does your duty of confidentiality end?', 'options' => [
                    'It continues even after you leave D\'Kings Men Media' => true,
                    'At the end of each working day' => false,
                    'When your contract ends' => false,
                    'When the information is more than one year old' => false,
                ]],
            ],
        ],

        [
            'title' => 'Module 4: Everyday security habits',
            'lessons' => [
                [
                    'title' => 'Passwords and multi-factor authentication',
                    'image' => ['file' => 'passwords-and-mfa.png', 'alt' => 'Strong, unique passphrases and turning on multi-factor authentication everywhere.'],
                    'content' => <<<'HTML'
<p>Stolen or guessed passwords are behind a large share of account takeovers. A few habits make a huge difference.</p>
<h1>Strong, unique passwords</h1>
<ul>
<li>Use a <strong>passphrase</strong> of four or more random words, for example <em>mango-radio-lantern-seventy</em>. Length beats complexity.</li>
<li>Use a <strong>different password for every account</strong>. When one site is breached, criminals try the same password everywhere else.</li>
<li>Use the company-approved <strong>password manager</strong> to create and store passwords. Don't keep them in notebooks, sticky notes or phone notes.</li>
<li><strong>Never share your password</strong> — not with colleagues, not with your manager, not with "IT support" on the phone. We will never ask for it.</li>
</ul>
<h1>Multi-factor authentication (MFA)</h1>
<p>MFA means that even if someone steals your password, they still can't log in without your second factor.</p>
<ul>
<li>Turn on MFA for email, admin dashboards, cloud storage, social media accounts and payment tools.</li>
<li>Prefer an <strong>authenticator app</strong> over SMS codes. SMS codes can be stolen through <strong>SIM-swap</strong> fraud — a risk we know well from protecting fans.</li>
<li>If you receive an MFA prompt you didn't trigger, <strong>deny it and report it</strong>. Someone may have your password.</li>
</ul>
<blockquote><strong>Shared accounts:</strong> official artist and brand social accounts must be accessed through the approved account-management tools with individual logins, not by passing one password around a group chat.</blockquote>
HTML,
                ],
                [
                    'title' => 'Phishing and social engineering',
                    'image' => ['file' => 'phishing.png', 'alt' => 'Common phishing tricks aimed at our team, red flags, and the Stop, Verify, Report steps.'],
                    'content' => <<<'HTML'
<p>Attackers find it easier to trick people than to break into systems. In our world, they often pretend to be artists, managers, telco partners or fans.</p>
<h1>Common tricks aimed at our team</h1>
<ul>
<li><strong>Fake partner emails:</strong> "Urgent: MTN settlement report — log in to view" with a link to a look-alike login page.</li>
<li><strong>Executive impersonation:</strong> a WhatsApp message from a new number with a senior manager's or artist's photo, asking you to urgently buy airtime or gift cards, send money, or share a file.</li>
<li><strong>Payment-change requests:</strong> a "vendor" or "artist manager" asking to change the bank account for a payout.</li>
<li><strong>Fake fans:</strong> someone calling support, pretending to be a subscriber, to get an account's phone number or email changed so they can take it over.</li>
<li><strong>Booking and collaboration bait:</strong> attachments pretending to be contracts, riders or demo tracks that contain malware.</li>
</ul>
<h1>Red flags</h1>
<ul>
<li>Urgency, secrecy or pressure ("do this now, don't tell anyone").</li>
<li>Requests for passwords, OTPs, PINs or payments.</li>
<li>Sender addresses or links that are slightly off (for example, <em>dkingsmen-media.co</em> or a free email address).</li>
<li>Unexpected attachments, especially .zip, .exe or documents that ask you to "enable content".</li>
</ul>
<h1>What to do</h1>
<ol>
<li><strong>Stop.</strong> Don't click, reply or open attachments.</li>
<li><strong>Verify</strong> through a channel you already trust — call the person on the number you have on file.</li>
<li><strong>Report</strong> it to the security contact or your manager, even if you already clicked. Fast reporting limits the damage.</li>
</ol>
HTML,
                ],
                [
                    'title' => 'Devices, Wi-Fi and working on the move',
                    'image' => ['file' => 'device-security.png', 'alt' => 'Six device habits: lock your screen, update promptly, encrypt, mind public Wi-Fi, keep a clear desk, and report lost devices immediately.'],
                    'content' => <<<'HTML'
<p>Our team works in offices, studios, backstage and on the road. Your laptop and phone carry access to fan data and unreleased content, so treat them like keys to the building.</p>
<h1>Your devices</h1>
<ul>
<li><strong>Lock your screen</strong> every time you step away (Windows + L, or Control + Command + Q on Mac). Set an automatic lock of 5 minutes or less.</li>
<li>Keep the operating system, browser and apps <strong>updated</strong>. Updates fix security holes attackers actively use.</li>
<li>Use device <strong>encryption</strong> and a PIN or biometric lock on phones.</li>
<li>Only install software from official stores or the IT team. No cracked software — it often contains malware.</li>
<li>Don't plug in unknown USB drives, including ones handed out at events.</li>
</ul>
<h1>Networks</h1>
<ul>
<li>Avoid public Wi-Fi at hotels, venues and airports for work. Use your phone's hotspot or the company VPN instead.</li>
<li>Never log in to admin dashboards on shared or public computers.</li>
</ul>
<h1>Physical security</h1>
<ul>
<li>Keep a <strong>clear desk</strong>: lock away documents and don't leave notes, contracts or printouts lying around.</li>
<li>Be aware of people looking over your shoulder at events and on public transport.</li>
<li>Challenge or report unfamiliar people in restricted areas such as studios and server rooms.</li>
</ul>
<blockquote><strong>Lost or stolen device?</strong> Report it <strong>immediately</strong>, even at night or at the weekend. The IT team can lock and wipe it remotely, but only if they know.</blockquote>
HTML,
                ],
            ],
            'quiz' => [
                ['q' => 'Which is the strongest password practice?', 'options' => [
                    'A long, unique passphrase stored in the approved password manager' => true,
                    'One strong password reused for all work accounts' => false,
                    'Your name followed by the current year' => false,
                    'A short complex password written on a sticky note' => false,
                ]],
                ['q' => 'Why is an authenticator app preferred over SMS codes for MFA?', 'options' => [
                    'SMS codes can be intercepted through SIM-swap fraud' => true,
                    'Authenticator apps don\'t need a password at all' => false,
                    'SMS codes never arrive in Nigeria' => false,
                    'There is no difference' => false,
                ]],
                ['q' => 'A WhatsApp message from an unknown number, with a senior manager\'s photo, urgently asks you to buy airtime vouchers. What should you do?', 'options' => [
                    'Don\'t act; verify by calling the manager on their known number and report it' => true,
                    'Buy the vouchers quickly — they said it was urgent' => false,
                    'Reply asking for more details, then decide' => false,
                    'Forward it to colleagues to see if they got it too' => false,
                ]],
                ['q' => 'Select ALL the phishing red flags.', 'options' => [
                    'Pressure to act urgently and keep it secret' => true,
                    'A request for your password or OTP' => true,
                    'A sender domain that is slightly misspelled' => true,
                    'A message from a colleague\'s known address about a meeting you arranged' => false,
                ]],
                ['q' => 'Your work phone is stolen on a Saturday night. When should you report it?', 'options' => [
                    'Immediately, so it can be locked and wiped remotely' => true,
                    'On Monday morning' => false,
                    'Only if it had fan data on it' => false,
                    'There\'s no need if it had a screen lock' => false,
                ]],
            ],
        ],

        [
            'title' => 'Module 5: Handling fan and payment data safely',
            'lessons' => [
                [
                    'title' => 'Access to systems: least privilege',
                    'image' => ['file' => 'fan-and-payment-data.png', 'alt' => 'Module overview: least privilege, payments and CREAM Fund, support and USSD, and sharing with partners.'],
                    'content' => <<<'HTML'
<p>The fewer people who can reach data, the smaller the damage when something goes wrong. That's the principle of <strong>least privilege</strong>.</p>
<ul>
<li>Only request access to the systems and data your role needs. Ask for extra access through your manager, and give it up when the task ends.</li>
<li><strong>Never share logins</strong> for admin dashboards, payment tools or content systems. Every action must be traceable to one person.</li>
<li>Don't look up fans, artists or colleagues out of curiosity. Viewing records without a work reason is a misuse of access, even if you're allowed into the system.</li>
<li><strong>No bulk exports</strong> of subscriber or transaction data to spreadsheets, personal email or chat apps without written approval. Where an export is approved, remove every column that isn't needed and delete the file when finished.</li>
<li>Managers: review who has access every quarter, and remove access on the day someone leaves or changes role.</li>
</ul>
<blockquote><strong>Scenario:</strong> A colleague on leave asks you to log in to the campaign dashboard with their password "just this once". The right answer is to decline and ask your manager to grant you access properly. Shared logins break our audit trail and our obligations under the NDPA.</blockquote>
HTML,
                ],
                [
                    'title' => 'Payments, CREAM Fund and auctions',
                    'content' => <<<'HTML'
<p>Money attracts fraud. These rules protect fans and the business.</p>
<h1>Card and bank details</h1>
<ul>
<li>Payments are handled by our payment providers' secure checkout. <strong>Never</strong> collect card numbers, CVVs, PINs or bank login details by phone, chat, email or form — not even to "help" a fan.</li>
<li>Never store payment details in tickets, notes or spreadsheets.</li>
</ul>
<h1>Refunds, payouts and bank changes</h1>
<ul>
<li>Verify every request to change where money is sent (artist payouts, vendor accounts, refund destinations) with a <strong>call-back to a number already on file</strong>, not the number in the request.</li>
<li>Apply two-person approval for large or unusual payments.</li>
</ul>
<h1>CREAM Fund</h1>
<ul>
<li>Instalment and repayment information reveals a fan's financial situation. Access is strictly need-to-know.</li>
<li>Only use it for administering the plan. Never use it for gossip, profiling or unrelated marketing.</li>
</ul>
<h1>Celebrity auctions</h1>
<ul>
<li>Keep bidders' identities and winning bids confidential unless the winner has agreed to be announced.</li>
<li>Share a winner's delivery address only with the logistics partner handling delivery, and delete it once delivery and any return period are complete.</li>
<li>Never reveal reserve prices or other bidders' offers.</li>
</ul>
HTML,
                ],
                [
                    'title' => 'Customer support, USSD and account security',
                    'content' => <<<'HTML'
<p>Support teams are a prime target for account takeover. Attackers pose as fans to get a phone number or email changed, then take over the account.</p>
<h1>Verify before you change anything</h1>
<ul>
<li>Follow the identity verification steps in the support procedure for <strong>every</strong> change to a phone number, email, password or payout detail.</li>
<li>Be extra careful with requests to move an account to a new phone number. This is a classic sign of SIM-swap fraud.</li>
<li>Don't let a caller's frustration, urgency or claimed fame push you into skipping verification.</li>
</ul>
<h1>Never ask for secrets</h1>
<ul>
<li>We never need a fan's password, PIN or one-time code (OTP). If a fan reads one out, tell them to change it.</li>
<li>Remind fans that CREAM will never ask for their OTP or PIN. Scammers pretending to be us do.</li>
</ul>
<h1>Minimise what you record</h1>
<ul>
<li>Write only what's needed in tickets. Mask phone numbers and emails where you can (for example, 0803****567).</li>
<li>Don't paste screenshots of full account pages into chat groups.</li>
</ul>
<blockquote><strong>Scenario:</strong> A caller says they're a well-known artist's manager and demands that the artist's CREAM account be moved to a new phone number "before the launch in an hour". Follow the verification procedure anyway. A real manager will understand; an impostor will get angry or give up.</blockquote>
HTML,
                ],
                [
                    'title' => 'Sharing data with partners, retention and deletion',
                    'content' => <<<'HTML'
<h1>Sharing with partners and vendors</h1>
<p>We work with telcos, payment providers, SMS aggregators, logistics companies, agencies and brand sponsors. Before any personal data is shared:</p>
<ul>
<li>There must be a <strong>lawful basis</strong> and a <strong>written agreement</strong> (a data processing or data sharing agreement) that limits what the partner may do with it.</li>
<li>Share <strong>only the fields they need</strong>. For reporting and analytics, use aggregated or anonymised figures instead of lists of individuals.</li>
<li>Use secure transfer methods — never plain email attachments or chat apps for personal data.</li>
<li><strong>Transfers outside Nigeria</strong> (for example, to overseas cloud services or international partners) are only allowed where the NDPA's conditions for cross-border transfers are met. Check with the DPO first.</li>
</ul>
<h1>Keep it only as long as needed</h1>
<ul>
<li>Follow the retention schedule. When data is no longer needed, delete it securely.</li>
<li>Delete temporary exports, downloads and working copies as soon as the task is done. Check your Downloads folder, email attachments and chat history.</li>
<li>Old devices and drives must go to the IT team for secure wiping. Never sell, give away or bin them.</li>
</ul>
<blockquote><strong>Rule of thumb:</strong> the safest data is the data we don't have. Before collecting, sharing or keeping anything, ask "do we really need this?"</blockquote>
HTML,
                ],
            ],
            'quiz' => [
                ['q' => 'A fan in a live chat offers to send their card number so you can "process the payment faster". What do you do?', 'options' => [
                    'Refuse, and direct them to the secure checkout' => true,
                    'Accept it, then delete the chat afterwards' => false,
                    'Ask them to send it by email instead' => false,
                    'Write it in the ticket for the finance team' => false,
                ]],
                ['q' => 'An email from an "artist manager" asks to change the artist\'s payout bank account. What must happen first?', 'options' => [
                    'Verify with a call-back to a number already on file, not one in the email' => true,
                    'Update it right away to avoid delaying payment' => false,
                    'Reply to the email to ask if they\'re sure' => false,
                    'Nothing — managers are allowed to change accounts' => false,
                ]],
                ['q' => 'What does "least privilege" mean?', 'options' => [
                    'People only get the access their role needs, for as long as they need it' => true,
                    'Junior staff get no system access' => false,
                    'Everyone shares one admin account' => false,
                    'Access is given to anyone who asks politely' => false,
                ]],
                ['q' => 'Select ALL the correct practices for auction winners\' delivery addresses.', 'options' => [
                    'Share them only with the logistics partner delivering the item' => true,
                    'Delete them after delivery and any return period' => true,
                    'Post the winner\'s full name and area on social media to celebrate' => false,
                    'Keep them indefinitely in a spreadsheet in case they\'re useful' => false,
                ]],
                ['q' => 'A brand sponsor wants campaign results. What is the best thing to share?', 'options' => [
                    'Aggregated or anonymised figures, under a written agreement' => true,
                    'The full list of participating fans\' phone numbers' => false,
                    'Screenshots of the admin dashboard' => false,
                    'Admin login details so they can check themselves' => false,
                ]],
            ],
        ],

        [
            'title' => 'Module 6: Incidents — spot it, report it, contain it',
            'lessons' => [
                [
                    'title' => 'What counts as a security incident',
                    'content' => <<<'HTML'
<p>A security incident is anything that puts the <strong>confidentiality, integrity or availability</strong> of our information at risk. A <strong>personal data breach</strong> is an incident where personal data is lost, destroyed, changed, disclosed or accessed without authorisation — whether by accident or on purpose.</p>
<h1>Examples</h1>
<ul>
<li>An email containing fan details sent to the wrong person.</li>
<li>A lost or stolen laptop, phone or hard drive.</li>
<li>An unreleased track or artwork appearing online.</li>
<li>A suspicious login alert, or an MFA prompt you didn't trigger.</li>
<li>Clicking a phishing link or entering your password on a fake page.</li>
<li>Files suddenly encrypted or renamed (possible ransomware).</li>
<li>A partner telling us their systems have been compromised.</li>
<li>A fan reporting that their account was changed without their knowledge.</li>
</ul>
<blockquote>If you're not sure whether something is an incident, <strong>report it anyway</strong>. We would much rather check a false alarm than miss a real breach.</blockquote>
HTML,
                ],
                [
                    'title' => 'How to report and what happens next',
                    'image' => ['file' => 'incident-response.png', 'alt' => 'Incidents must be reported immediately — the NDPC must be notified within 72 hours — with what to do and what not to do.'],
                    'content' => <<<'HTML'
<h1>Report immediately</h1>
<p>Tell your manager and the Data Protection Officer or security contact <strong>as soon as you notice</strong> — don't wait until you've "confirmed" it or finished your shift. Include:</p>
<ul>
<li>What happened and when you noticed.</li>
<li>What information or systems may be affected.</li>
<li>What you've already done.</li>
</ul>
<h1>Why speed matters</h1>
<p>Under the NDPA, when a personal data breach is likely to put people's rights and freedoms at risk, D'Kings Men Media must notify the <strong>Nigeria Data Protection Commission within 72 hours</strong> of becoming aware of it. Where the risk to individuals is high, we must also tell the affected people without delay so they can protect themselves. That clock starts when <em>anyone</em> in the company knows, so every hour you wait eats into it.</p>
<h1>Do</h1>
<ul>
<li>Disconnect a device you suspect is infected from Wi-Fi and the network, but don't switch it off.</li>
<li>Ask the wrong recipient of a misdirected email to delete it and confirm they've done so.</li>
<li>Keep evidence: messages, emails, screenshots and times.</li>
</ul>
<h1>Don't</h1>
<ul>
<li>Don't try to investigate or "fix" it yourself beyond these steps.</li>
<li>Don't delete emails, logs or files related to the incident.</li>
<li>Don't pay a ransom or negotiate with attackers.</li>
<li>Don't discuss the incident on social media or with the press. Refer all questions to the communications team.</li>
</ul>
<blockquote>Nobody will be punished for honestly reporting a mistake. Hiding one is what causes real damage.</blockquote>
HTML,
                ],
                [
                    'title' => 'Summary: our golden rules',
                    'image' => ['file' => 'golden-rules.png', 'alt' => 'The ten golden rules of data security and confidentiality at D\'Kings Men Media.'],
                    'content' => <<<'HTML'
<p>You've reached the end of the lessons. Here's everything in ten rules.</p>
<ol>
<li><strong>Know what you handle.</strong> Fan data, payments, CREAM Fund plans, auction details, talent entries and unreleased content all need protecting.</li>
<li><strong>Follow the NDPA principles.</strong> Collect only what we need, use it only for the stated purpose, keep it accurate and don't keep it too long.</li>
<li><strong>Respect fans' rights.</strong> Pass any request about personal data to the DPO immediately.</li>
<li><strong>Classify and handle accordingly.</strong> Unreleased music, contracts and bulk data are Restricted.</li>
<li><strong>Keep secrets secret.</strong> No leaks, no teasers, no confirmations before official announcements.</li>
<li><strong>Use strong, unique passwords and MFA.</strong> Never share them.</li>
<li><strong>Stop, verify, report</strong> anything that looks like phishing or impersonation.</li>
<li><strong>Least privilege.</strong> Access only what you need, and never share logins.</li>
<li><strong>Protect payments.</strong> Never collect card details, and verify every bank change by call-back.</li>
<li><strong>Report incidents immediately.</strong> The 72-hour clock starts when anyone knows.</li>
</ol>
<p>Mark this lesson complete, then take the <strong>Final Assessment</strong>. You need 80% to pass and you have up to 3 attempts. Good luck!</p>
HTML,
                ],
            ],
            'quiz' => [
                ['q' => 'Which of these is a personal data breach?', 'options' => [
                    'An email with a list of fans\' phone numbers sent to the wrong partner' => true,
                    'A fan buying a song' => false,
                    'Publishing an approved press release' => false,
                    'A scheduled system update' => false,
                ]],
                ['q' => 'Within how long must the NDPC be notified of a reportable personal data breach?', 'options' => [
                    '72 hours of becoming aware of it' => true,
                    '7 days' => false,
                    '30 days' => false,
                    'Only if a journalist finds out' => false,
                ]],
                ['q' => 'You think your laptop has ransomware. What should you do first?', 'options' => [
                    'Disconnect it from the network, leave it on, and report it immediately' => true,
                    'Switch it off and reinstall Windows' => false,
                    'Pay the ransom quickly to limit damage' => false,
                    'Carry on working and mention it later' => false,
                ]],
                ['q' => 'Select ALL the things you should NOT do after an incident.', 'options' => [
                    'Delete the related emails to tidy up' => true,
                    'Post about it on social media' => true,
                    'Try to negotiate with the attacker' => true,
                    'Keep screenshots and note the times' => false,
                ]],
                ['q' => 'You\'re not sure whether something counts as an incident. What should you do?', 'options' => [
                    'Report it anyway' => true,
                    'Wait to see if it happens again' => false,
                    'Only report it if you\'re certain' => false,
                    'Ask colleagues in a group chat first' => false,
                ]],
            ],
        ],
    ],

    'final' => [
        'title' => 'Data Security & Confidentiality Essentials — Final Assessment',
        'slug' => 'data-security-confidentiality-final-assessment',
        'description' => '<p>The final assessment for the Data Security &amp; Confidentiality Essentials course. It covers every module, with an emphasis on real situations you may face at D\'Kings Men Media.</p>',
        'instructions' => <<<'HTML'
<p>This assessment has <strong>30 questions</strong> in three sections, and you have <strong>40 minutes</strong>.</p>
<ul>
<li>You need <strong>80%</strong> to pass. You have up to <strong>3 attempts</strong>.</li>
<li>Some questions ask you to <strong>select all that apply</strong>. You must choose every correct option, and only the correct options, to get the mark.</li>
<li>Questions appear in a random order. You can move between questions and flag ones to come back to.</li>
<li>This assessment is monitored: leaving the exam window or copying text is recorded.</li>
<li>Work on your own and don't use notes. This checks what <em>you</em> know.</li>
</ul>
HTML,
        'duration_minutes' => 40,
        'pass_percentage' => 80,
        'max_attempts' => 3,
        'sections' => [
            [
                'title' => 'Section A: The law and your obligations',
                'questions' => [
                    ['q' => 'The Nigeria Data Protection Act 2023 is enforced by:', 'options' => [
                        'The Nigeria Data Protection Commission (NDPC)' => true,
                        'The Nigerian Communications Commission (NCC)' => false,
                        'The Economic and Financial Crimes Commission (EFCC)' => false,
                        'The Nigerian Copyright Commission' => false,
                    ]],
                    ['q' => 'D\'Kings Men Media decides why and how CREAM fan data is used. Under the NDPA, that makes it a:', 'options' => [
                        'Data controller' => true,
                        'Data processor' => false,
                        'Data subject' => false,
                        'Data broker' => false,
                    ]],
                    ['q' => 'Phone numbers were collected to deliver purchased songs. Marketing wants to give them to a drinks sponsor for its own promotions. Which principle does this most directly raise?', 'options' => [
                        'Purpose limitation — it\'s a new, unrelated purpose needing its own lawful basis' => true,
                        'Accuracy' => false,
                        'Data portability' => false,
                        'Storage limitation' => false,
                    ]],
                    ['q' => 'Select ALL the lawful bases for processing personal data under the NDPA.', 'options' => [
                        'Consent' => true,
                        'Performance of a contract' => true,
                        'Compliance with a legal obligation' => true,
                        'The data is useful for future campaigns' => false,
                    ]],
                    ['q' => 'A 16-year-old enters a talent-discovery competition. Before using their details and video, we need:', 'options' => [
                        'Consent from a parent or legal guardian' => true,
                        'Only the contestant\'s own consent' => false,
                        'Nothing, because entering the competition implies consent' => false,
                        'Approval from the telco partner' => false,
                    ]],
                    ['q' => 'Which of these is valid consent for promotional SMS?', 'options' => [
                        'A fan actively opts in to promotional messages and can opt out easily' => true,
                        'A pre-ticked box on the sign-up form' => false,
                        'Assuming consent because the fan hasn\'t complained' => false,
                        'Buying a song, which automatically signs the fan up for promotions' => false,
                    ]],
                    ['q' => 'A fan emails: "Please delete all my data from CREAM." What is the correct first step?', 'options' => [
                        'Forward it to the DPO immediately; identity must be verified before acting' => true,
                        'Delete the account right away without checking who sent it' => false,
                        'Reply that we can\'t delete data once it\'s collected' => false,
                        'Ignore it unless they ask again' => false,
                    ]],
                    ['q' => 'Select ALL the rights that data subjects have under the NDPA.', 'options' => [
                        'Access a copy of their personal data' => true,
                        'Have inaccurate data corrected' => true,
                        'Object to direct marketing' => true,
                        'Access other fans\' purchase histories' => false,
                    ]],
                    ['q' => 'For an organisation of major importance, NDPA fines can reach:', 'options' => [
                        'The higher of ₦10 million or 2% of annual gross revenue' => true,
                        'A maximum of ₦50,000' => false,
                        'Nothing — the NDPA only issues warnings' => false,
                        '₦1,000 per affected fan, capped at ₦1 million' => false,
                    ]],
                    ['q' => 'Before sending fan data to a partner\'s servers outside Nigeria, you should:', 'options' => [
                        'Check with the DPO that the NDPA\'s cross-border transfer conditions are met' => true,
                        'Go ahead if the partner is a well-known international brand' => false,
                        'Email the file, since email is secure enough' => false,
                        'Remove only the fans\' names and send the rest' => false,
                    ]],
                ],
            ],
            [
                'title' => 'Section B: Confidentiality and everyday security',
                'questions' => [
                    ['q' => 'What classification applies to an artist\'s unreleased album and its release date?', 'options' => [
                        'Restricted' => true,
                        'Internal' => false,
                        'Public' => false,
                        'Confidential only for the artwork' => false,
                    ]],
                    ['q' => 'A DJ friend asks you to send a new single a week before release "so I can build hype". What do you do?', 'options' => [
                        'Decline and refer them to the promotions team for an approved, watermarked copy under embargo' => true,
                        'Send it over WhatsApp but ask them not to share it' => false,
                        'Send a low-quality recording from your phone instead' => false,
                        'Post a snippet on your status so everyone gets the hype' => false,
                    ]],
                    ['q' => 'Select ALL the correct ways to protect unreleased content.', 'options' => [
                        'Share through company secure file-sharing with named recipients and expiring links' => true,
                        'Watermark previews sent outside the company' => true,
                        'Delete local copies once your work is finished' => true,
                        'Keep a backup on your personal cloud drive in case the company system is down' => false,
                    ]],
                    ['q' => 'At a party, someone asks whether a rumoured D\'banj collaboration is real. It is, but hasn\'t been announced. The best response is:', 'options' => [
                        '"I can\'t comment on that — keep an eye on the official channels."' => true,
                        'Confirm it, since people will know soon anyway' => false,
                        'Show them the artwork on your phone' => false,
                        'Deny it firmly to throw them off' => false,
                    ]],
                    ['q' => 'Which password is best for your work email?', 'options' => [
                        'A long, unique passphrase stored in the approved password manager, with MFA turned on' => true,
                        'The same strong password you use for your personal accounts' => false,
                        'Dkm2026! — short but complex' => false,
                        'Your phone number, because it\'s easy to remember' => false,
                    ]],
                    ['q' => 'You get an MFA approval prompt on your phone that you didn\'t trigger. What should you do?', 'options' => [
                        'Deny it and report it immediately — someone may have your password' => true,
                        'Approve it to make the notifications stop' => false,
                        'Ignore it; it\'s probably a glitch' => false,
                        'Turn off MFA so it doesn\'t happen again' => false,
                    ]],
                    ['q' => 'An email that looks like it\'s from a telco partner says "Your settlement report is ready — log in here". The link goes to "mtn-settlements-portal.co". What should you do?', 'options' => [
                        'Don\'t click; verify with the partner through a known contact and report it as phishing' => true,
                        'Log in quickly to download the report' => false,
                        'Forward it to the whole team so they can check too' => false,
                        'Reply asking whether it\'s genuine' => false,
                    ]],
                    ['q' => 'Select ALL the good device habits.', 'options' => [
                        'Lock your screen every time you step away' => true,
                        'Install updates promptly' => true,
                        'Use your phone\'s hotspot or the VPN rather than venue Wi-Fi' => true,
                        'Plug in the free USB drive you were given at an industry event' => false,
                    ]],
                    ['q' => 'Who should have access to official artist social media accounts?', 'options' => [
                        'Named people through the approved management tool, each with their own login' => true,
                        'Anyone in the team group chat where the password is pinned' => false,
                        'Only the intern, to save time' => false,
                        'Anyone who asks the artist directly' => false,
                    ]],
                    ['q' => 'Your confidentiality obligations to D\'Kings Men Media:', 'options' => [
                        'Continue even after you leave the company' => true,
                        'End when you leave the company' => false,
                        'Only apply during office hours' => false,
                        'Only apply to documents marked "Confidential"' => false,
                    ]],
                ],
            ],
            [
                'title' => 'Section C: Fan data, payments and incidents',
                'questions' => [
                    ['q' => 'A colleague on leave asks you to use their admin login to finish a campaign. What do you do?', 'options' => [
                        'Decline and ask your manager to grant you access properly' => true,
                        'Use it this once — it\'s for work' => false,
                        'Use it and change their password afterwards' => false,
                        'Share it with the rest of the team so anyone can help' => false,
                    ]],
                    ['q' => 'You\'re curious about a celebrity\'s CREAM purchases. You have access to the admin dashboard. Should you look them up?', 'options' => [
                        'No — accessing records without a work reason is misuse, even with system access' => true,
                        'Yes, because you already have access' => false,
                        'Yes, as long as you don\'t tell anyone' => false,
                        'Only if the celebrity is a D\'Kings Men artist' => false,
                    ]],
                    ['q' => 'A caller claims to be a fan who has lost their SIM and wants their CREAM account moved to a new number immediately. What do you do?', 'options' => [
                        'Complete the full identity verification procedure before making any change' => true,
                        'Change it straight away — they sound genuine' => false,
                        'Ask them to read out the OTP sent to the old number' => false,
                        'Change it, but only if they know the account email' => false,
                    ]],
                    ['q' => 'Select ALL the things we must NEVER ask a fan for.', 'options' => [
                        'Their password' => true,
                        'Their bank card PIN' => true,
                        'A one-time code (OTP) sent to their phone' => true,
                        'Their username, to find their account' => false,
                    ]],
                    ['q' => 'A fan wants to pay for a CREAM Fund plan by reading their card number to you over the phone. You should:', 'options' => [
                        'Politely refuse and guide them to the secure payment checkout' => true,
                        'Take the details and enter them yourself' => false,
                        'Write them down and pass them to finance' => false,
                        'Ask them to WhatsApp a photo of the card' => false,
                    ]],
                    ['q' => 'An email from a vendor asks to update their bank details for the next payment. The correct process is:', 'options' => [
                        'Verify with a call-back to a phone number already on file before changing anything' => true,
                        'Call the number in the email signature to confirm' => false,
                        'Update it, since the email came from their usual address' => false,
                        'Pay into both the old and new accounts to be safe' => false,
                    ]],
                    ['q' => 'A celebrity auction has ended. What should happen to the winner\'s delivery address?', 'options' => [
                        'Share it only with the logistics partner, and delete it after delivery and any return period' => true,
                        'Publish it so fans can see where the item went' => false,
                        'Keep it permanently for future marketing' => false,
                        'Share it with the celebrity\'s fan club' => false,
                    ]],
                    ['q' => 'You accidentally email a spreadsheet of subscriber phone numbers to the wrong agency. What should you do?', 'options' => [
                        'Report it immediately and ask the recipient to delete it and confirm' => true,
                        'Say nothing and hope they don\'t open it' => false,
                        'Delete it from your Sent folder so there\'s no record' => false,
                        'Wait until the end of the week to see if anything happens' => false,
                    ]],
                    ['q' => 'Why must possible data breaches be reported to the DPO immediately?', 'options' => [
                        'The company may have only 72 hours to notify the NDPC, starting when anyone becomes aware' => true,
                        'So the person responsible can be fired quickly' => false,
                        'Because reporting after 24 hours is illegal for employees' => false,
                        'It isn\'t urgent; breaches can be reported at the monthly meeting' => false,
                    ]],
                    ['q' => 'Select ALL the correct actions if you suspect ransomware on your laptop.', 'options' => [
                        'Disconnect it from Wi-Fi and the network' => true,
                        'Leave it switched on' => true,
                        'Report it immediately' => true,
                        'Pay the ransom to get the files back quickly' => false,
                    ]],
                ],
            ],
        ],
    ],
];
