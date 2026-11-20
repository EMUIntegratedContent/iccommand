/**
 * Switches for features that are built but not yet open to users.
 */

/**
 * Scholarship <-> advisor links. While false, the Advisors app hides scholarships (the list's
 * Scholarships column and the form's Scholarships usage section) and the scholarship form hides
 * its advisor cards. Programs <-> advisors are unaffected. Only the UI is hidden: links already
 * saved are kept (both forms send them back unchanged on save) and the API still accepts them.
 */
export const SCHOLARSHIP_ADVISORS_ENABLED = false
