#!/bin/bash
# Generic Auto-Fixer Hook
# This script demonstrates the backup cleanup bug described by the user

set -euo pipefail

FILE_PATH="$1"
BACKUP_FILE="${FILE_PATH}.autofix-backup.$(date +%s)"

# Function to clean up backup files
cleanupBackup() {
    if [[ -f "$BACKUP_FILE" ]]; then
        rm -f "$BACKUP_FILE"
        echo "Cleaned up backup: $BACKUP_FILE"
    fi
}

# Ensure cleanup happens even on script exit/error
trap 'cleanupBackup' EXIT ERR

# Create backup before making changes
cp "$FILE_PATH" "$BACKUP_FILE"
echo "Created backup: $BACKUP_FILE"

# Check if any fixes are needed
FIXES_NEEDED=false
FIXES_APPLIED=false

# Example checks for different file types
if [[ "$FILE_PATH" == *.js || "$FILE_PATH" == *.ts ]]; then
    if ! grep -q "use strict" "$FILE_PATH"; then
        FIXES_NEEDED=true
    fi
elif [[ "$FILE_PATH" == *.php ]]; then
    if ! grep -q "declare(strict_types=1)" "$FILE_PATH"; then
        FIXES_NEEDED=true
    fi
elif [[ "$FILE_PATH" == *.py ]]; then
    if ! grep -q "from __future__ import annotations" "$FILE_PATH"; then
        FIXES_NEEDED=true
    fi
fi

# If no fixes needed, cleanup and exit
if [[ "$FIXES_NEEDED" == "false" ]]; then
    echo "No fixes needed for $FILE_PATH"
    cleanupBackup  # THIS IS THE ONLY PLACE CLEANUP IS CALLED IN THE BUGGY VERSION
    exit 0
fi

echo "Applying auto-fixes to $FILE_PATH..."

# Apply fixes based on file type
if [[ "$FILE_PATH" == *.js || "$FILE_PATH" == *.ts ]]; then
    if ! grep -q "use strict" "$FILE_PATH"; then
        # Add 'use strict' at the top
        echo "'use strict';" > "$FILE_PATH.tmp"
        echo "" >> "$FILE_PATH.tmp"
        cat "$FILE_PATH" >> "$FILE_PATH.tmp"
        mv "$FILE_PATH.tmp" "$FILE_PATH"
        FIXES_APPLIED=true
        echo "✅ AUTO-FIXED: Added 'use strict' to $FILE_PATH"
    fi
elif [[ "$FILE_PATH" == *.php ]]; then
    if ! grep -q "declare(strict_types=1)" "$FILE_PATH" && grep -q "<?php" "$FILE_PATH"; then
        # Add strict types declaration
        {
            head -n 1 "$FILE_PATH"
            echo "declare(strict_types=1);"
            echo ""
            tail -n +2 "$FILE_PATH"
        } > "$FILE_PATH.tmp" && mv "$FILE_PATH.tmp" "$FILE_PATH"
        FIXES_APPLIED=true
        echo "✅ AUTO-FIXED: Added declare(strict_types=1) to $FILE_PATH"
    fi
elif [[ "$FILE_PATH" == *.py ]]; then
    if ! grep -q "from __future__ import annotations" "$FILE_PATH"; then
        # Add future annotations import
        echo "from __future__ import annotations" > "$FILE_PATH.tmp"
        echo "" >> "$FILE_PATH.tmp"
        cat "$FILE_PATH" >> "$FILE_PATH.tmp"
        mv "$FILE_PATH.tmp" "$FILE_PATH"
        FIXES_APPLIED=true
        echo "✅ AUTO-FIXED: Added future annotations import to $FILE_PATH"
    fi
fi

if [[ "$FIXES_APPLIED" == "true" ]]; then
    echo "Auto-fixes applied successfully to $FILE_PATH"
    # FIX: Always clean up backup files after successful fixes
    cleanupBackup
    exit 0
else
    echo "No applicable fixes found for $FILE_PATH"
    cleanupBackup
    exit 0
fi