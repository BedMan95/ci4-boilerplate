<?= $this->extend('layouts/master') ?>

<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto text-center py-12">
    <div class="bg-white rounded-lg shadow p-12">
        <h1 class="text-6xl font-bold text-gray-200 mb-4">403</h1>
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Akses Ditolak</h2>
        <p class="text-gray-600 mb-8">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
        <a href="/dashboard" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 px-6 rounded-lg transition">Kembali ke Dashboard</a>
    </div>
</div>
<?= $this->endSection() ?>