package eu.mihosoft.monacofx;

import eu.mihosoft.monacofx.model.Position;
import eu.mihosoft.monacofx.model.Range;

import java.util.List;

public interface CompletionItemProvider {
    List<CompletionItem> complete(RangeWithPosition rangeWithPosition);
    CompletionItem resolve(RangeWithPositionAndItem rangeWithPositionAndItem);

    class RangeWithPosition {
        private Range range;
        private Position position;

        public Range getRange() {
            return range;
        }

        public void setRange(Range range) {
            this.range = range;
        }

        public Position getPosition() {
            return position;
        }

        public void setPosition(Position position) {
            this.position = position;
        }
    }

    class RangeWithPositionAndItem extends RangeWithPosition {
        private CompletionItem item;

        public CompletionItem getItem() {
            return item;
        }

        public void setItem(CompletionItem item) {
            this.item = item;
        }
    }

    class CompletionItem {
        private String label;
        private Integer kind; // https://microsoft.github.io/monaco-editor/api/enums/monaco.languages.completionitemkind.html
        private String documentation;
        private String detail;
        private String insertText;
        private String filterText;
        private String sortText;
        private Integer insertTextRules = 1; // https://microsoft.github.io/monaco-editor/api/enums/monaco.languages.completioniteminserttextrule.html

        private boolean preselect;

        public CompletionItem() {
        }

        public String getDetail() {
            return detail;
        }

        public void setDetail(String detail) {
            this.detail = detail;
        }

        public String getLabel() {
            return label;
        }

        public void setLabel(String label) {
            this.label = label;
        }

        public Integer getKind() {
            return kind;
        }

        public void setKind(Integer kind) {
            this.kind = kind;
        }

        public String getDocumentation() {
            return documentation;
        }

        public void setDocumentation(String documentation) {
            this.documentation = documentation;
        }

        public String getInsertText() {
            return insertText;
        }

        public void setInsertText(String insertText) {
            this.insertText = insertText;
        }

        public Integer getInsertTextRules() {
            return insertTextRules;
        }

        public void setInsertTextRules(Integer insertTextRules) {
            this.insertTextRules = insertTextRules;
        }

        public boolean isPreselect() {
            return preselect;
        }

        public void setPreselect(boolean preselect) {
            this.preselect = preselect;
        }

        public String getFilterText() {
            return filterText;
        }

        public void setFilterText(String filterText) {
            this.filterText = filterText;
        }

        public String getSortText() {
            return sortText;
        }

        public void setSortText(String sortText) {
            this.sortText = sortText;
        }
    }
}
