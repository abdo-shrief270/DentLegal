import { defineStore } from 'pinia'
import type { CitationRef, DraftContent, DraftParagraph, ReviewDetail } from '~/types/api'

type SectionIndex = number | 'intro'

/** State of the lawyer's review workspace: the loaded draft, the version being viewed and the working copy being edited. */
export const useReviewStore = defineStore('review', () => {
  const api = useApi()

  const detail = ref<ReviewDetail | null>(null)
  const viewedVersionNo = ref<number | null>(null)
  const editing = ref(false)
  const working = ref<DraftContent | null>(null)
  const saving = ref(false)

  const latest = computed(() => detail.value?.versions.at(-1) ?? null)
  const viewed = computed(() => detail.value?.versions.find((v) => v.no === viewedVersionNo.value) ?? latest.value)
  const isApproved = computed(() => detail.value?.status === 'approved')
  const content = computed<DraftContent | null>(() => (editing.value ? working.value : viewed.value?.content ?? null))
  const dirty = computed(() => editing.value && JSON.stringify(working.value) !== JSON.stringify(latest.value?.content))

  /** Legal claims without at least one verified knowledge-source citation are flagged «غير مسند». */
  function isUnsupported(paragraph: DraftParagraph): boolean {
    return paragraph.legalClaim && !paragraph.citations.some((c) => c.ref.startsWith('S') && c.verified)
  }

  const flaggedCount = computed(() => {
    const c = content.value
    if (!c) return 0
    return [c.intro, ...c.sections.flatMap((s) => s.paragraphs)].filter(isUnsupported).length
  })

  async function load(draftId: string) {
    detail.value = null
    editing.value = false
    working.value = null
    detail.value = await api.lawyer.getReview(draftId)
    viewedVersionNo.value = latest.value?.no ?? null
  }

  function startEditing() {
    if (!latest.value || isApproved.value) return
    working.value = structuredClone(toRaw(latest.value.content))
    viewedVersionNo.value = latest.value.no
    editing.value = true
  }

  function cancelEditing() {
    editing.value = false
    working.value = null
  }

  function paragraphsOf(section: SectionIndex): DraftParagraph[] {
    if (!working.value) return []
    return section === 'intro' ? [working.value.intro] : working.value.sections[section]!.paragraphs
  }

  function addParagraph(section: number) {
    working.value?.sections[section]!.paragraphs.push({ id: `p${Date.now()}`, text: '', legalClaim: false, citations: [] })
  }

  function removeParagraph(section: number, index: number) {
    working.value?.sections[section]!.paragraphs.splice(index, 1)
  }

  function moveParagraph(section: number, index: number, delta: -1 | 1) {
    const list = working.value?.sections[section]!.paragraphs
    if (!list) return
    const target = index + delta
    if (target < 0 || target >= list.length) return
    const [item] = list.splice(index, 1)
    list.splice(target, 0, item!)
  }

  function removeCitation(paragraph: DraftParagraph, index: number) {
    paragraph.citations.splice(index, 1)
  }

  /** Lawyer-added citations point at a retrieved source; the lawyer is the author, so they count as verified. */
  function addCitation(paragraph: DraftParagraph, ref: string) {
    if (paragraph.citations.some((c) => c.ref === ref)) return
    const citation: CitationRef = { ref, quote: '', verified: true }
    paragraph.citations.push(citation)
    if (ref.startsWith('S')) paragraph.legalClaim = true
  }

  async function saveVersion(note: string) {
    if (!detail.value || !working.value) return
    saving.value = true
    try {
      detail.value = await api.lawyer.saveVersion(detail.value.draftId, working.value, note)
      viewedVersionNo.value = latest.value?.no ?? null
      editing.value = false
      working.value = null
    } finally {
      saving.value = false
    }
  }

  async function approve() {
    if (!detail.value) return
    saving.value = true
    try {
      detail.value = await api.lawyer.approve(detail.value.draftId)
      viewedVersionNo.value = latest.value?.no ?? null
    } finally {
      saving.value = false
    }
  }

  async function addNote(text: string) {
    if (!detail.value) return
    detail.value.notes.push(await api.lawyer.addNote(detail.value.draftId, text))
  }

  return {
    detail, viewedVersionNo, editing, working, saving, latest, viewed, isApproved, content, dirty, flaggedCount,
    isUnsupported, load, startEditing, cancelEditing, paragraphsOf, addParagraph, removeParagraph, moveParagraph,
    removeCitation, addCitation, saveVersion, approve, addNote,
  }
})
