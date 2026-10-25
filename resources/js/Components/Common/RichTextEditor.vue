<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import { watch } from 'vue';
import {
    Bold,
    Italic,
    Underline as UnderlineIcon,
    List,
    ListOrdered,
    Heading1,
    Heading2,
    Heading3,
    AlignLeft,
    AlignCenter,
    AlignRight,
    Undo,
    Redo,
} from 'lucide-vue-next';

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Underline,
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-xl max-w-none focus:outline-none min-h-[400px] p-5',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        const isSame = editor.value?.getHTML() === value;
        if (!isSame && editor.value) {
            editor.value.commands.setContent(value, { emitUpdate: false });
        }
    },
);
</script>

<template>
    <div v-if="editor" class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
        <!-- Toolbar -->
        <div class="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50 p-2 dark:border-slate-700 dark:bg-slate-800/50">
            <!-- Text Formatting -->
            <button
                @click="editor.chain().focus().toggleBold().run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive('bold') }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Bold"
            >
                <Bold :size="18" />
            </button>
            <button
                @click="editor.chain().focus().toggleItalic().run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive('italic') }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Italic"
            >
                <Italic :size="18" />
            </button>
            <button
                @click="editor.chain().focus().toggleUnderline().run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive('underline') }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Underline"
            >
                <UnderlineIcon :size="18" />
            </button>

            <div class="mx-1 h-6 w-px bg-slate-200 dark:bg-slate-700"></div>

            <!-- Headings -->
            <button
                @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive('heading', { level: 1 }) }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Heading 1"
            >
                <Heading1 :size="18" />
            </button>
            <button
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive('heading', { level: 2 }) }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Heading 2"
            >
                <Heading2 :size="18" />
            </button>
            <button
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive('heading', { level: 3 }) }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Heading 3"
            >
                <Heading3 :size="18" />
            </button>

            <div class="mx-1 h-6 w-px bg-slate-200 dark:bg-slate-700"></div>

            <!-- Lists -->
            <button
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive('bulletList') }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Bullet List"
            >
                <List :size="18" />
            </button>
            <button
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive('orderedList') }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Numbered List"
            >
                <ListOrdered :size="18" />
            </button>

            <div class="mx-1 h-6 w-px bg-slate-200 dark:bg-slate-700"></div>

            <!-- Alignment -->
            <button
                @click="editor.chain().focus().setTextAlign('left').run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive({ textAlign: 'left' }) }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Align Left"
            >
                <AlignLeft :size="18" />
            </button>
            <button
                @click="editor.chain().focus().setTextAlign('center').run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive({ textAlign: 'center' }) }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Align Center"
            >
                <AlignCenter :size="18" />
            </button>
            <button
                @click="editor.chain().focus().setTextAlign('right').run()"
                :class="{ 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400': editor.isActive({ textAlign: 'right' }) }"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                type="button"
                title="Align Right"
            >
                <AlignRight :size="18" />
            </button>

            <div class="mx-1 h-6 w-px bg-slate-200 dark:bg-slate-700"></div>

            <!-- Undo/Redo -->
            <button
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().undo()"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-slate-700"
                type="button"
                title="Undo"
            >
                <Undo :size="18" />
            </button>
            <button
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().redo()"
                class="rounded-lg p-2 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-slate-700"
                type="button"
                title="Redo"
            >
                <Redo :size="18" />
            </button>
        </div>

        <!-- Editor Content -->
        <EditorContent :editor="editor" class="bg-slate-50 dark:bg-slate-800" />
    </div>
</template>

<style>
/* TipTap Editor Styles */
.ProseMirror {
    outline: none;
}

.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
}

.ProseMirror h1 {
    font-size: 2em;
    font-weight: bold;
    margin-top: 0.67em;
    margin-bottom: 0.67em;
}

.ProseMirror h2 {
    font-size: 1.5em;
    font-weight: bold;
    margin-top: 0.83em;
    margin-bottom: 0.83em;
}

.ProseMirror h3 {
    font-size: 1.17em;
    font-weight: bold;
    margin-top: 1em;
    margin-bottom: 1em;
}

.ProseMirror ul,
.ProseMirror ol {
    padding-left: 1.5rem;
    margin: 1rem 0;
}

.ProseMirror ul {
    list-style-type: disc;
}

.ProseMirror ol {
    list-style-type: decimal;
}

.ProseMirror li {
    margin: 0.25rem 0;
}

.ProseMirror a {
    color: #3b82f6;
    text-decoration: underline;
    cursor: pointer;
}

.ProseMirror a:hover {
    color: #2563eb;
}

.ProseMirror strong {
    font-weight: bold;
}

.ProseMirror em {
    font-style: italic;
}

.ProseMirror u {
    text-decoration: underline;
}

.ProseMirror p {
    margin: 0.5rem 0;
}
</style>
