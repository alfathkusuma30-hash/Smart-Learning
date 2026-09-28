<?php
$user_type = $_SESSION['user_type'];

$courses = [
    [
        'code' => 'TRC4-4-3',
        'title' => 'Applied Negotiation Techniques',
        'category' => 'SOFT SKILL AND OTHERS',
        'type' => 'Inhouse',
        'total' => 'IDR 0',
        'plan' => '0 Person',
        'required_new' => 'No',
        'duration' => '2 Hari (16 Jam)',
        'level' => 'Intermediate',
        'desc' => 'Strategi negosiasi praktis, resolusi konflik kepentingan operasional, dan teknik mencapai kesepakatan win-win di lingkungan industri pertambangan.'
    ],
    [
        'code' => 'TRC5-46',
        'title' => 'CS Excellence and Handling Customer Complaint',
        'category' => 'SERTIFIKASI',
        'type' => 'Public',
        'total' => 'IDR 0',
        'plan' => '0 Person',
        'required_new' => 'No',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Advanced',
        'desc' => 'Standar pelayanan prima korporat, penanganan keluhan pelanggan eksternal dan stakeholder tambang dengan protokol komunikasi profesional bersertifikasi.',
        'is_active' => true // Highlighted with signature teal border like in the reference image
    ],
    [
        'code' => 'TRC4-5-26',
        'title' => 'Internal Auditor ISO 50001 Sistem Manajemen Energi',
        'category' => 'TECH-CORP SERVICES',
        'type' => 'Inhouse',
        'total' => 'IDR 0',
        'plan' => '0 Person',
        'required_new' => 'No',
        'duration' => '4 Hari (32 Jam)',
        'level' => 'Advanced',
        'desc' => 'Audit internal efisiensi energi operasional pabrik pengolahan emas dan instalasi kelistrikan bawah tanah site Pongkor.'
    ],
    [
        'code' => 'TRC5-70',
        'title' => 'Sertifikasi Instruktur BNSP KKNI Level 4',
        'category' => 'SERTIFIKASI',
        'type' => 'Public',
        'total' => 'IDR 0',
        'plan' => '0 Person',
        'required_new' => 'No',
        'duration' => '5 Hari (40 Jam)',
        'level' => 'Advanced',
        'desc' => 'Sertifikasi lisensi instruktur pelatihan kerja nasional berstandar BNSP untuk instruktur internal ANTAM Learning Center.'
    ],
    [
        'code' => 'TRC1-9',
        'title' => '17th MGEI Annual Convention',
        'category' => 'FOUNDATION',
        'type' => 'Public',
        'total' => 'IDR 0',
        'plan' => '0 Person',
        'required_new' => 'No',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Intermediate',
        'desc' => 'Konvensi tahunan Masyarakat Geologi Ekonomi Indonesia, perkembangan eksplorasi mineral dan teknologi ekstraksi bijih emas terkini.'
    ],
    [
        'code' => 'K3-ESDM-01',
        'title' => 'POP Level 1 — Pengawas Operasional Pertama',
        'category' => 'REGULASI K3 ESDM',
        'type' => 'Wajib',
        'total' => 'IDR 0',
        'plan' => '24 Person',
        'required_new' => 'Yes',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Basic',
        'desc' => 'Standar kompetensi pengawas operasional pertambangan mineral dan batubara. Wajib bagi seluruh asisten foreman & supervisor lapangan Site Pongkor.'
    ],
    [
        'code' => 'K3-ESDM-02',
        'title' => 'Safety Induction Site Tambang Pongkor',
        'category' => 'KESELAMATAN PERTAMBANGAN',
        'type' => 'Wajib',
        'total' => 'IDR 0',
        'plan' => '50 Person',
        'required_new' => 'Yes',
        'duration' => '1 Hari (8 Jam)',
        'level' => 'Basic',
        'desc' => 'Pengenalan bahaya bawah tanah, pengoperasian SCSR, evakuasi gas beracun, dan etika keselamatan site UBPN Bogor.'
    ]
];
$initialQuery = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
?>

<div class="learning-bank-container">
    <!-- SunFish 3-Field Filter Section -->
    <div class="sunfish-filter-bar">
        <!-- Field 1: Training Course -->
        <div class="sf-filter-field">
            <label class="sf-filter-label">Training Course</label>
            <input type="text" id="sf-filter-course" class="sf-filter-input" placeholder="Search Training Course Or Training Code" value="<?php echo $initialQuery; ?>" onkeyup="filterSunfishCourses()">
        </div>

        <!-- Field 2: Training Category -->
        <div class="sf-filter-field">
            <label class="sf-filter-label">Training Category</label>
            <input type="text" id="sf-filter-category" class="sf-filter-input" placeholder="Search Training Category" onkeyup="filterSunfishCourses()">
        </div>

        <!-- Field 3: Training Type -->
        <div class="sf-filter-field">
            <label class="sf-filter-label">Training Type</label>
            <select id="sf-filter-type" class="sf-filter-select" onchange="filterSunfishCourses()">
                <option value="">Select Training Type</option>
                <option value="Inhouse">Inhouse</option>
                <option value="Public">Public</option>
                <option value="Wajib">Wajib</option>
            </select>
        </div>

        <!-- View Toggle Buttons -->
        <div class="sf-filter-field" style="align-items: flex-end;">
            <div class="view-toggle-group">
                <button type="button" class="view-btn" id="btn-view-grid" onclick="toggleCourseView('grid')" title="Grid View">
                    <i class="ph-bold ph-squares-four"></i>
                </button>
                <button type="button" class="view-btn active" id="btn-view-list" onclick="toggleCourseView('list')" title="List View">
                    <i class="ph-bold ph-list-dashes"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Course Cards List (Direct match to reference image!) -->
    <div class="sf-courses-list" id="courses-container">
        <?php foreach ($courses as $c): ?>
        <div class="sf-course-card <?php echo !empty($c['is_active']) ? 'highlight-selected' : ''; ?>" 
             data-code="<?php echo htmlspecialchars(strtolower($c['code'])); ?>"
             data-title="<?php echo htmlspecialchars(strtolower($c['title'])); ?>"
             data-category="<?php echo htmlspecialchars(strtolower($c['category'])); ?>"
             data-type="<?php echo htmlspecialchars(strtolower($c['type'])); ?>">
             
            <!-- Column 1: Code, Title, Category -->
            <div class="sf-card-main-col">
                <div class="sf-course-code"><?php echo htmlspecialchars($c['code']); ?></div>
                <div class="sf-course-title"><?php echo htmlspecialchars($c['title']); ?></div>
                <div class="sf-course-category"><?php echo htmlspecialchars($c['category']); ?></div>
            </div>

            <!-- Column 2: Badge (Inhouse / Public / Wajib) -->
            <div class="sf-card-badge-col">
                <span class="sf-pill-badge <?php echo strtolower($c['type']); ?>">
                    <?php echo htmlspecialchars($c['type']); ?>
                </span>
            </div>

            <!-- Column 3: Total -->
            <div class="sf-card-meta-col">
                <span class="sf-meta-lbl">Total</span>
                <span class="sf-meta-val"><?php echo htmlspecialchars($c['total']); ?></span>
            </div>

            <!-- Column 4: Training Plan -->
            <div class="sf-card-meta-col">
                <span class="sf-meta-lbl">Training Plan</span>
                <span class="sf-meta-val"><?php echo htmlspecialchars($c['plan']); ?></span>
            </div>

            <!-- Column 5: Required For New Employee -->
            <div class="sf-card-meta-col">
                <span class="sf-meta-lbl">Required For New Employee</span>
                <span class="sf-meta-val"><?php echo htmlspecialchars($c['required_new']); ?></span>
            </div>

            <!-- Column 6: Action Buttons -->
            <div class="sf-card-actions-col">
                <button type="button" class="sf-btn-outline" onclick="openCourseDetailModal('<?php echo addslashes($c['title']); ?>', '<?php echo addslashes($c['type']); ?>', '<?php echo addslashes($c['duration']); ?>', '<?php echo addslashes($c['level']); ?>', '<?php echo addslashes($c['desc']); ?>')">
                    See Description
                </button>
                <?php if ($user_type === 'Admin'): ?>
                <button type="button" class="sf-btn-teal" onclick="openAssignTrainingModal('<?php echo addslashes($c['title']); ?>')">
                    Create Event
                </button>
                <?php else: ?>
                <button type="button" class="sf-btn-teal" onclick="openNewActivityModalWithPreset('<?php echo addslashes($c['title']); ?>', '<?php echo addslashes($c['type']); ?>', '<?php echo addslashes($c['category']); ?>', '<?php echo addslashes($c['duration']); ?>', '<?php echo addslashes($c['level']); ?>')">
                    Create Event
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty state search notice -->
    <div id="no-courses-notice" style="display: none; text-align: center; padding: 3rem 1.5rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-top: 1rem;">
        <i class="ph-bold ph-magnifying-glass" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 0.5rem;"></i>
        <h3 style="font-size: 1.1rem; color: #0f172a; margin-bottom: 0.25rem;">Tidak ada modul yang cocok</h3>
        <p style="font-size: 0.85rem; color: #64748b;">Silakan sesuaikan kata kunci pencarian atau filter tipe di atas.</p>
    </div>

    <!-- Form Tambah Training Baru untuk Admin -->
    <?php if($user_type == 'Admin'): ?>
    <div id="add-training-section" class="mt-6" style="margin-top: 2.5rem;">
        <div class="page-top-header">
            <h2 class="page-title" style="font-size: 1.25rem;">
                <i class="ph-bold ph-plus-circle text-accent"></i> Tambah Master Pelatihan Baru
            </h2>
        </div>
        
        <div class="glass-panel" style="background: white; border-radius: var(--radius-md); padding: 1.75rem;">
            <form id="form-add-training" onsubmit="handleAddNewTraining(event)" class="admin-form">
                <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                    <div class="sf-filter-field">
                        <label class="sf-filter-label">KODE TRAINING</label>
                        <input type="text" id="new-code" placeholder="cth. TRC5-99" class="sf-filter-input" required>
                    </div>

                    <div class="sf-filter-field">
                        <label class="sf-filter-label">NAMA TRAINING / MODUL *</label>
                        <input type="text" id="new-title" placeholder="cth. Manajemen Risiko Geoteknik Pongkor" class="sf-filter-input" required>
                    </div>
                    
                    <div class="sf-filter-field">
                        <label class="sf-filter-label">KATEGORI TRAINING</label>
                        <input type="text" id="new-category" placeholder="cth. TEKNIK TAMBANG" class="sf-filter-input" required>
                    </div>

                    <div class="sf-filter-field">
                        <label class="sf-filter-label">TIPE KURIKULUM *</label>
                        <select id="new-type" class="sf-filter-select" required>
                            <option value="Inhouse">Inhouse</option>
                            <option value="Public">Public</option>
                            <option value="Wajib">Wajib (K3 ESDM)</option>
                        </select>
                    </div>
                    
                    <div class="sf-filter-field">
                        <label class="sf-filter-label">DURASI PEMBELAJARAN *</label>
                        <input type="text" id="new-duration" placeholder="cth. 3 Hari (24 Jam)" class="sf-filter-input" required>
                    </div>
                    
                    <div class="sf-filter-field">
                        <label class="sf-filter-label">LEVEL KOMPETENSI *</label>
                        <select id="new-level" class="sf-filter-select" required>
                            <option value="Basic">Basic</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>
                    
                    <div class="sf-filter-field" style="grid-column: 1 / -1;">
                        <label class="sf-filter-label">DESKRIPSI & SILABUS MATERI</label>
                        <textarea id="new-desc" placeholder="Tuliskan gambaran umum materi, target peserta, dan kompetensi yang diuji..." class="sf-filter-input" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="flex items-center gap-3 mt-4">
                    <button type="submit" class="btn-dark">
                        <i class="ph-bold ph-floppy-disk"></i>
                        <span>Simpan ke Katalog Master</span>
                    </button>
                    <button type="reset" class="btn-outline">Reset</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function filterSunfishCourses() {
    const courseQuery = (document.getElementById('sf-filter-course').value || '').toLowerCase().trim();
    const categoryQuery = (document.getElementById('sf-filter-category').value || '').toLowerCase().trim();
    const typeQuery = (document.getElementById('sf-filter-type').value || '').toLowerCase().trim();

    const cards = document.querySelectorAll('.sf-course-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const title = card.getAttribute('data-title') || '';
        const code = card.getAttribute('data-code') || '';
        const category = card.getAttribute('data-category') || '';
        const type = card.getAttribute('data-type') || '';

        const matchCourse = !courseQuery || title.includes(courseQuery) || code.includes(courseQuery);
        const matchCategory = !categoryQuery || category.includes(categoryQuery);
        const matchType = !typeQuery || type.toLowerCase() === typeQuery.toLowerCase();

        if (matchCourse && matchCategory && matchType) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const notice = document.getElementById('no-courses-notice');
    if (notice) {
        notice.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function toggleCourseView(mode) {
    const container = document.getElementById('courses-container');
    const btnGrid = document.getElementById('btn-view-grid');
    const btnList = document.getElementById('btn-view-list');

    if (mode === 'grid') {
        container.classList.add('grid-view');
        btnGrid.classList.add('active');
        btnList.classList.remove('active');
    } else {
        container.classList.remove('grid-view');
        btnGrid.classList.remove('active');
        btnList.classList.add('active');
    }
}

function handleAddNewTraining(e) {
    e.preventDefault();
    const code = document.getElementById('new-code').value || 'TRC-NEW';
    const title = document.getElementById('new-title').value;
    const category = document.getElementById('new-category').value || 'GENERAL';
    const type = document.getElementById('new-type').value;
    const duration = document.getElementById('new-duration').value;
    const level = document.getElementById('new-level').value;
    const desc = document.getElementById('new-desc').value || 'Pelatihan baru.';

    const container = document.getElementById('courses-container');
    const newCard = document.createElement('div');
    newCard.className = 'sf-course-card highlight-selected';
    newCard.setAttribute('data-code', code.toLowerCase());
    newCard.setAttribute('data-title', title.toLowerCase());
    newCard.setAttribute('data-category', category.toLowerCase());
    newCard.setAttribute('data-type', type.toLowerCase());

    newCard.innerHTML = `
        <div class="sf-card-main-col">
            <div class="sf-course-code">${code}</div>
            <div class="sf-course-title">${title}</div>
            <div class="sf-course-category">${category}</div>
        </div>
        <div class="sf-card-badge-col">
            <span class="sf-pill-badge ${type.toLowerCase()}">${type}</span>
        </div>
        <div class="sf-card-meta-col">
            <span class="sf-meta-lbl">Total</span>
            <span class="sf-meta-val">IDR 0</span>
        </div>
        <div class="sf-card-meta-col">
            <span class="sf-meta-lbl">Training Plan</span>
            <span class="sf-meta-val">0 Person</span>
        </div>
        <div class="sf-card-meta-col">
            <span class="sf-meta-lbl">Required For New Employee</span>
            <span class="sf-meta-val">No</span>
        </div>
        <div class="sf-card-actions-col">
            <button type="button" class="sf-btn-outline" onclick="openCourseDetailModal('${title}', '${type}', '${duration}', '${level}', '${desc}')">
                See Description
            </button>
            <button type="button" class="sf-btn-teal" onclick="openAssignTrainingModal('${title}')">
                Create Event
            </button>
        </div>
    `;

    container.prepend(newCard);
    document.getElementById('form-add-training').reset();
    showToast(`Modul "${title}" berhasil ditambahkan ke katalog master!`, 'success');
}
</script>
