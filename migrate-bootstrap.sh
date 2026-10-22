#!/bin/bash
# Bootstrap to Tailwind CSS Migration Script
# Run from project root

echo "╔══════════════════════════════════════════════════╗"
echo "║  Bootstrap → Tailwind CSS Migration             ║"
echo "╚══════════════════════════════════════════════════╝"

VIEWS_DIR="resources/views"
FILES_MODIFIED=0

# Function to apply replacements
migrate_file() {
    local file="$1"
    local modified=0
    
    # Grid system
    sed -i 's/class="row"/class="grid grid-cols-12 gap-4/g' "$file"
    sed -i 's/class="row g-4"/class="grid grid-cols-12 gap-4/g' "$file"
    sed -i 's/class="row mb-2"/class="grid grid-cols-12 gap-4 mb-2/g' "$file"
    sed -i 's/class="row justify-content-center"/class="grid grid-cols-12 gap-4 justify-center/g' "$file"
    sed -i 's/<div class="col-md-3">/<div class="col-span-12 md:col-span-3">/g' "$file"
    sed -i 's/<div class="col-md-4">/<div class="col-span-12 md:col-span-4">/g' "$file"
    sed -i 's/<div class="col-md-6">/<div class="col-span-12 md:col-span-6">/g' "$file"
    sed -i 's/<div class="col-md-8">/<div class="col-span-12 md:col-span-8">/g' "$file"
    sed -i 's/<div class="col-md-12">/<div class="col-span-12">/g' "$file"
    sed -i 's/<div class="col-sm-6">/<div class="col-span-12 sm:col-span-6">/g' "$file"
    sed -i 's/<div class="col-lg-8">/<div class="col-span-12 lg:col-span-8">/g' "$file"
    sed -i 's/<div class="col-lg-10">/<div class="col-span-12 lg:col-span-10">/g' "$file"
    sed -i 's/<div class="col-lg-3">/<div class="col-span-12 lg:col-span-3">/g' "$file"
    sed -i 's/<div class="col-xl-3">/<div class="col-span-12 xl:col-span-3">/g' "$file"
    
    # Cards
    sed -i 's/class="card card-primary"/class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"/g' "$file"
    sed -i 's/class="card card-outline card-primary"/class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700"/g' "$file"
    sed -i 's/class="card card-outline card-warning"/class="bg-white rounded-xl shadow-sm border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700"/g' "$file"
    sed -i 's/class="card"/class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"/g' "$file"
    sed -i 's/class="card mb-6"/class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6"/g' "$file"
    sed -i 's/<div class="card-header"/<div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"/g' "$file"
    sed -i 's/<div class="card-body"/<div class="p-6"/g' "$file"
    sed -i 's/<div class="card-title"/<h3 class="font-semibold text-gray-900 dark:text-white"/g' "$file"
    sed -i 's/<h3 class="card-title"/<h3 class="font-semibold text-gray-900 dark:text-white"/g' "$file"
    sed -i 's/<div class="card-text"/<p class="text-gray-600 dark:text-gray-400"/g' "$file"
    sed -i 's|</div>N|</div>|g' "$file"
    
    # Buttons
    sed -i 's/class="btn btn-primary"/class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-secondary"/class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-success"/class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-danger"/class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-warning"/class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-info"/class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 inline-flex items-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-light"/class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 inline-flex items-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-dark"/class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 inline-flex items-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-outline-secondary"/class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-outline-primary"/class="border border-indigo-600 text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 inline-flex items-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-link"/class="text-indigo-600 hover:text-indigo-700 underline font-medium"/g' "$file"
    sed -i 's/class="btn btn-lg"/class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors text-lg"/g' "$file"
    sed -i 's/class="btn btn-sm"/class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm"/g' "$file"
    sed -i 's/class="btn btn-block"/class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center justify-center gap-2 font-medium transition-colors"/g' "$file"
    sed -i 's/class="btn btn-primary btn-lg"/class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors text-lg"/g' "$file"
    sed -i 's/class="btn btn-primary btn-sm"/class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm"/g' "$file"
    
    # Forms
    sed -i 's/class="form-control\b"/class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"/g' "$file"
    sed -i 's/class="form-control /class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white /g' "$file"
    sed -i 's/class="form-select"/class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"/g' "$file"
    sed -i 's/class="form-label"/class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"/g' "$file"
    sed -i 's/class="form-group"/class="mb-4"/g' "$file"
    sed -i 's/class="form-check"/class="flex items-center gap-2"/g' "$file"
    sed -i 's/class="form-check-input"/class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"/g' "$file"
    sed -i 's/class="form-check-label"/class="text-sm text-gray-600 dark:text-gray-400"/g' "$file"
    sed -i 's/class="invalid-feedback"/class="text-red-500 text-sm mt-1"/g' "$file"
    sed -i 's/class="is-invalid"/class="border-red-500 focus:ring-red-500 focus:border-red-500"/g' "$file"
    
    # Badges
    sed -i 's/class="badge badge-primary"/class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-indigo-900 dark:text-indigo-300"/g' "$file"
    sed -i 's/class="badge badge-secondary"/class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300"/g' "$file"
    sed -i 's/class="badge badge-success"/class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300"/g' "$file"
    sed -i 's/class="badge badge-danger"/class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300"/g' "$file"
    sed -i 's/class="badge badge-warning"/class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-yellow-900 dark:text-yellow-300"/g' "$file"
    sed -i 's/class="badge badge-info"/class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300"/g' "$file"
    sed -i 's/class="badge bg-light text-dark p-2"/class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full"/g' "$file"
    sed -i 's/class="badge badge-pill"/class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full"/g' "$file"
    
    # Alerts
    sed -i 's/class="alert alert-success"/class="bg-green-50 text-green-800 border border-green-200 rounded-lg p-4 mb-4"/g' "$file"
    sed -i 's/class="alert alert-danger"/class="bg-red-50 text-red-800 border border-red-200 rounded-lg p-4 mb-4"/g' "$file"
    sed -i 's/class="alert alert-warning"/class="bg-yellow-50 text-yellow-800 border border-yellow-200 rounded-lg p-4 mb-4"/g' "$file"
    sed -i 's/class="alert alert-info"/class="bg-blue-50 text-blue-800 border border-blue-200 rounded-lg p-4 mb-4"/g' "$file"
    sed -i 's/class="alert alert-error"/class="bg-red-50 text-red-800 border border-red-200 rounded-lg p-4 mb-4"/g' "$file"
    sed -i 's/class="alert-dismissible"/class="bg-blue-50 text-blue-800 border border-blue-200 rounded-lg p-4 mb-4 flex justify-between"/g' "$file"
    
    # Tables
    sed -i 's/class="table"/class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"/g' "$file"
    sed -i 's/class="table table-striped"/class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"/g' "$file"
    sed -i 's/class="table table-hover"/class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50"/g' "$file"
    sed -i 's/class="table table-bordered"/class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200"/g' "$file"
    sed -i 's/class="table table-sm"/class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm"/g' "$file"
    
    # Progress bars
    sed -i 's/class="progress"/class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700"/g' "$file"
    sed -i 's/class="progress progress-sm"/class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700"/g' "$file"
    sed -i 's/<div class="progress-bar bg-success"/<div class="bg-green-600 h-2 rounded-full"/g' "$file"
    sed -i 's/<div class="progress-bar bg-info"/<div class="bg-blue-600 h-2 rounded-full"/g' "$file"
    sed -i 's/<div class="progress-bar bg-primary"/<div class="bg-indigo-600 h-2 rounded-full"/g' "$file"
    sed -i 's/<div class="progress-bar bg-warning"/<div class="bg-yellow-500 h-2 rounded-full"/g' "$file"
    sed -i 's/<div class="progress-bar"/<div class="bg-indigo-600 h-2 rounded-full"/g' "$file"
    
    # Pagination
    sed -i 's/class="pagination"/class="flex gap-2"/g' "$file"
    sed -i 's/class="page-item"/class=""/g' "$file"
    sed -i 's/class="page-link"/class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-700"/g' "$file"
    sed -i 's/class="page-item active"/class=""/g' "$file"
    sed -i 's/class="page-item disabled"/class="opacity-50 cursor-not-allowed"/g' "$file"
    
    # Utilities (margin/padding)
    sed -i 's/ me-1/ ml-1/g' "$file" 2>/dev/null
    sed -i 's/ me-2/ ml-2/g' "$file" 2>/dev/null
    sed -i 's/ me-3/ ml-3/g' "$file" 2>/dev/null
    sed -i 's/ ms-1/ mr-1/g' "$file" 2>/dev/null
    sed -i 's/ ms-2/ mr-2/g' "$file" 2>/dev/null
    sed -i 's/ ms-3/ mr-3/g' "$file" 2>/dev/null
    
    FILES_MODIFIED=$((FILES_MODIFIED + 1))
}

# Find and migrate all dashboard views
find "$VIEWS_DIR" -name "*.blade.php" -not -path "*/layouts/*" -not -path "*/public/*" -not -path "*/auth/*" -not -path "*/components/*" -not -path "*/emails/*" -not -path "*/errors/*" -not -path "*/notifications/*" | while read -r file; do
    migrate_file "$file"
done

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║  Migration Complete!                             ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""
echo "Files modified: $FILES_MODIFIED"
echo ""
echo "Next steps:"
echo "  1. Review changes: git diff --stat"
echo "  2. Fix any remaining Bootstrap classes manually"
echo "  3. Run tests: php artisan test"
echo "  4. Build assets: npm run build"
